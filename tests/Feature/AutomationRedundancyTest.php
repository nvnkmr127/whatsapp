<?php

namespace Tests\Feature;

use App\Jobs\PersistMessageJob;
use App\Jobs\HandleIncomingWorkflowJob;
use App\Events\MessageReceived;
use App\Models\Team;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AutomationRedundancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_persist_message_job_does_not_dispatch_workflow_job_sync()
    {
        Bus::fake();
        Event::fake();

        $team = Team::factory()->create(['whatsapp_phone_number_id' => '123456789']);

        $payload = [
            'provider_id' => 'wamid.test',
            'to_phone_id' => '123456789',
            'from_phone' => '919876543210',
            'contact_name' => 'Tester',
            'content' => ['type' => 'text', 'text' => ['body' => 'Ping']],
            'timestamp' => time(),
        ];

        // Execute Job
        $job = new PersistMessageJob($payload);
        $job->handle();

        // Assert MessageReceived event IS dispatched (triggering the listener path)
        Event::assertDispatched(MessageReceived::class);

        // Assert HandleIncomingWorkflowJob is NOT dispatched synchronously (or queued)
        // Since we removed the direct dispatch.
        Bus::assertNotDispatched(HandleIncomingWorkflowJob::class);
        Bus::assertNotDispatchedSync(HandleIncomingWorkflowJob::class);
    }
}
