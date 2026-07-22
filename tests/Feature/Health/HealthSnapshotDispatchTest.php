<?php

namespace Tests\Feature\Health;

use App\Jobs\CreateHealthSnapshotJob;
use App\Models\Team;
use App\Models\WhatsAppHealthSnapshot;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HealthSnapshotDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_one_job_per_connected_team(): void
    {
        Queue::fake();

        $connected = Team::factory()->count(3)->create(['whatsapp_access_token' => 'tok']);
        Team::factory()->count(2)->create(['whatsapp_access_token' => null]);

        $this->artisan('whatsapp:calculate-health-scores')->assertSuccessful();

        Queue::assertPushed(CreateHealthSnapshotJob::class, 3);

        foreach ($connected as $team) {
            Queue::assertPushed(
                CreateHealthSnapshotJob::class,
                fn (CreateHealthSnapshotJob $job) => $job->team->is($team)
            );
        }
    }

    public function test_teams_without_a_token_are_skipped(): void
    {
        Queue::fake();

        Team::factory()->count(2)->create(['whatsapp_access_token' => null]);

        $this->artisan('whatsapp:calculate-health-scores')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_a_failing_team_does_not_block_the_others(): void
    {
        Queue::fake();

        Team::factory()->count(5)->create(['whatsapp_access_token' => 'tok']);

        $this->artisan('whatsapp:calculate-health-scores')->assertSuccessful();

        // The point of the refactor: dispatch is decoupled from execution, so
        // one team's Meta call can never stall the rest of the run.
        Queue::assertPushed(CreateHealthSnapshotJob::class, 5);
    }

    public function test_the_job_writes_a_snapshot(): void
    {
        $team = Team::factory()->create(['whatsapp_access_token' => 'tok']);

        $monitor = $this->mock(WhatsAppHealthMonitor::class);
        $monitor->shouldReceive('createSnapshot')
            ->once()
            ->with(\Mockery::on(fn ($t) => $t->is($team)))
            ->andReturn(new WhatsAppHealthSnapshot);

        (new CreateHealthSnapshotJob($team))->handle($monitor);
    }
}
