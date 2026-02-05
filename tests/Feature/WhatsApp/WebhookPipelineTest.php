<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\ProcessWebhookJob;
use App\Models\Message;
use App\Models\Team;
use App\Models\WebhookPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.whatsapp.client_secret', 'test-secret');
    }

    public function test_signature_verification_succeeds_with_valid_signature()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-secret');

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
        ])->postJson('/api/webhook/whatsapp', ['test' => 'data']);

        $response->assertStatus(200);
    }

    /*
    // Middleware is not applied to the route yet in api.php globally or individually? 
    // Wait, typically we apply it to the route. I need to check api.php or apply it.
    // Assuming I will update api.php in next step, or if it was auto-applied... 
    // Actually I haven't applied the middleware to the route yet!
    // I should do that. But let's write the test first.
    */

    public function test_webhook_storage_and_job_dispatch()
    {
        Queue::fake();

        $payload = ['entry' => []];
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), 'test-secret');

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
        ])->postJson('/api/webhook/whatsapp', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('webhook_payloads', [
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessWebhookJob::class);
    }

    public function test_job_processes_message_successfully()
    {
        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
        ]);

        $payloadData = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'from' => '9876543210',
                                        'id' => 'wamid.test',
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello World']
                                    ]
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Tester']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $payloadRecord = WebhookPayload::create([
            'payload' => $payloadData,
            'status' => 'pending'
        ]);

        $job = new ProcessWebhookJob($payloadRecord->id);
        $job->handle();

        $this->assertDatabaseHas('messages', [
            'whatsapp_message_id' => 'wamid.test',
            'content' => 'Hello World',
            'team_id' => $team->id,
        ]);

        $this->assertEquals('processed', $payloadRecord->fresh()->status);
    }

    public function test_job_handles_duplicate_message_idempotently()
    {
        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
        ]);

        // Create initial message
        Message::factory()->create([
            'whatsapp_message_id' => 'wamid.duplicate',
            'team_id' => $team->id,
        ]);

        $payloadData = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'from' => '9876543210',
                                        'id' => 'wamid.duplicate',
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello Again']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $payloadRecord = WebhookPayload::create([
            'payload' => $payloadData,
            'status' => 'pending'
        ]);

        $job = new ProcessWebhookJob($payloadRecord->id);
        $job->handle();

        $this->assertEquals('processed', $payloadRecord->fresh()->status);
        $this->assertCount(1, Message::where('whatsapp_message_id', 'wamid.duplicate')->get());
    }

    public function test_job_extracts_rich_media_content_correctly()
    {
        $team = Team::factory()->create(['whatsapp_phone_number_id' => '123456789']);

        $scenarios = [
            ['type' => 'image', 'image' => ['caption' => 'Nice Photo'], 'expected' => 'Nice Photo'],
            ['type' => 'interactive', 'interactive' => ['type' => 'button_reply', 'button_reply' => ['title' => 'Yes Please']], 'expected' => 'Yes Please'],
        ];

        foreach ($scenarios as $index => $data) {
            $data['id'] = "wamid.media.$index";
            $data['timestamp'] = time();

            $payloadData = [
                'entry' => [
                    [
                        'changes' => [
                            [
                                'value' => [
                                    'metadata' => ['phone_number_id' => '123456789'],
                                    'messages' => [$data + ['from' => '12345']],
                                    'contacts' => [['profile' => ['name' => 'Tester']]]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $payloadRecord = WebhookPayload::create([
                'payload' => $payloadData,
                'status' => 'pending'
            ]);

            $job = new ProcessWebhookJob($payloadRecord->id);
            $job->handle();

            $this->assertDatabaseHas('messages', [
                'whatsapp_message_id' => $data['id'],
                'content' => $data['expected'],
            ]);
        }
    }

    public function test_job_handles_stop_keyword()
    {
        $team = Team::factory()->create(['whatsapp_phone_number_id' => '123456789']);

        $payloadData = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'from' => '1112223333',
                                        'id' => 'wamid.stop',
                                        'type' => 'text',
                                        'text' => ['body' => 'STOP']
                                    ]
                                ],
                                'contacts' => [['profile' => ['name' => 'Stopper']]]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $payloadRecord = WebhookPayload::create([
            'payload' => $payloadData,
            'status' => 'pending'
        ]);

        $job = new ProcessWebhookJob($payloadRecord->id);
        $job->handle();

        $contact = \App\Models\Contact::where('phone_number', '1112223333')->first();
        $this->assertEquals('opted_out', $contact->opt_in_status);
        $this->assertDatabaseHas('consent_logs', [
            'contact_id' => $contact->id,
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD'
        ]);
    }
}
