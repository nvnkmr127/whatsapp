<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Jobs\PersistMessageJob;
use App\Jobs\UpdateMessageStatusJob;
use App\Jobs\HandleIncomingWorkflowJob;
use App\Jobs\ProcessAiAssistantJob;
use App\Models\Message;
use App\Models\Team;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AsyncJobChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_webhook_job_dispatches_messages_async()
    {
        Bus::fake();

        $job = new ProcessWebhookJob(1, 'trace_123');
        // We need to mock the payload retrieval or setup DB.
        // Assuming ProcessWebhookJob finds the payload by ID.
        // Let's create the payload.
        $payloadData = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.test',
                                        'from' => '123456',
                                        'type' => 'text',
                                        'text' => ['body' => 'Hi'],
                                        'timestamp' => time(),
                                    ]
                                ],
                                'metadata' => ['display_phone_number' => '123', 'phone_number_id' => '123'],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $payloadRec = \App\Models\WebhookPayload::create([
            'payload' => $payloadData,
            'signature' => 'sig',
            'status' => 'pending',
            'waba_id' => 'waba_1'
        ]);

        // Re-instantiate with correct ID
        $job = new ProcessWebhookJob($payloadRec->id, 'trace_123');

        $eventBusMock = \Mockery::mock(\App\Services\EventBusService::class);
        $eventBusMock->shouldReceive('publish')->andReturn(true);

        $job->handle($eventBusMock);

        // Assert PersistMessageJob is dispatched (ASYNC check)
        Bus::assertDispatched(PersistMessageJob::class);
        Bus::assertNotDispatchedSync(PersistMessageJob::class);
    }

    public function test_handle_incoming_workflow_job_dispatches_ai_async()
    {
        Bus::fake();

        $team = Team::factory()->create([
            'commerce_config' => ['ai_assistant_enabled' => true]
        ]);
        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $message = Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'type' => 'text',
            'content' => 'Hi AI',
            'whatsapp_message_id' => 'wamid.ai',
            'direction' => 'inbound'
        ]);

        $job = new HandleIncomingWorkflowJob($message->id, $team->id);
        $job->handle();

        Bus::assertDispatched(ProcessAiAssistantJob::class);
        Bus::assertNotDispatchedSync(ProcessAiAssistantJob::class);
    }
}
