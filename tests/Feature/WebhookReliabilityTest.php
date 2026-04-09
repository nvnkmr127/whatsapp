<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\Message;
use App\Models\WebhookPayload;
use App\Services\EventBusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WebhookReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a basic Team and Contact if necessary, though ProcessWebhookJob mainly hits WebhookPayload and EventBus
    }

    public function test_it_detects_duplicate_messages_and_skips_processing()
    {
        // 1. Arrange: Create an existing message with a known WAMID
        $wamid = 'wamid.HBgNM...';

        // We need a dummy team/contact/conversation to satisfy foreign keys if we insert a Message
        $team = \App\Models\Team::factory()->create(['whatsapp_phone_number_id' => '123456789']);
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id]);
        $conversation = \App\Models\Conversation::factory()->create(['team_id' => $team->id, 'contact_id' => $contact->id]);

        Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'whatsapp_message_id' => $wamid,
            'direction' => 'inbound',
            'status' => 'delivered',
            'type' => 'text',
        ]);

        // Create the WebhookPayload
        $payloadData = [
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'id' => $wamid,
                                        'from' => '1234567890',
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'Duplicate Hello'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $payload = WebhookPayload::create([
            'payload' => $payloadData,
            'status' => 'pending',
        ]);

        // Mock EventBusService to ensure NO publish calls happen
        $eventBusMock = Mockery::mock(EventBusService::class);
        $eventBusMock->shouldNotReceive('publish');

        // 2. Act
        $job = new ProcessWebhookJob($payload->id);
        $job->handle($eventBusMock);

        // 3. Assert
        $payload->refresh();
        $this->assertEquals('processed', $payload->status);
        $this->assertNull($payload->error_message);
    }

    public function test_it_throws_exception_if_eventbus_fails_to_publish()
    {
        // 1. Arrange: Create a payload
        $wamid = 'wamid.UniqueNew';
        $team = \App\Models\Team::factory()->create(['whatsapp_phone_number_id' => '123456789']);

        $payloadData = [
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'id' => $wamid,
                                        'from' => '1234567890',
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'Unique Hello'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $payload = WebhookPayload::create([
            'payload' => $payloadData, // WebhookPayload casts this to json/array automatically
            'status' => 'pending',
        ]);

        // Mock EventBusService to throw exception
        $eventBusMock = Mockery::mock(EventBusService::class);
        $eventBusMock->shouldReceive('publish')
            ->once()
            ->andThrow(new \Exception('EventBus failed to publish Inbound Message Event'));

        // 2. Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('EventBus failed to publish Inbound Message Event');

        $job = new ProcessWebhookJob($payload->id);
        $job->handle($eventBusMock);

        // Verify status is NOT 'processed' (it throws before that)
        // Note: The job throws, preventing status update to processed.
        // We added logic to update error_message but throw exception.

        $payload->refresh();
        $this->assertNotEquals('processed', $payload->status);
        $this->assertNotNull($payload->error_message);
    }
}
