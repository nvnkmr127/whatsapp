<?php

namespace Tests\Feature;

use App\Jobs\PersistMessageJob;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BroadcastConsumerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_dispatches_persist_job_asynchronously()
    {
        Queue::fake();

        // 1. Setup Data
        $team = Team::factory()->create();
        $eventId = 1;

        DB::table('broadcast_events')->insert([
            'id' => $eventId,
            'team_id' => $team->id,
            'event_type' => 'message.inbound',
            'payload' => json_encode(['foo' => 'bar']),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Run Command (only 1 count to process our event)
        $this->artisan('broadcast:consume', [
            '--count' => 1,
            '--seconds' => 1, // Limit run time
        ]);

        // 3. Assert Job Pushed (Not Sync)
        Queue::assertPushed(PersistMessageJob::class);

        // 4. Verification of "Zero Latency" intent:
        // If it was sync, the command would have executed the job logic inline.
        // Since we are mocking Queue, if it was dispatchSync, it might still fire,
        // but Queue::fake() interacts differently with sync.
        // Specifically, we want to ensure it went to the 'messages' queue.
        Queue::assertPushedOn('messages', PersistMessageJob::class);
    }
}
