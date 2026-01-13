<?php

namespace Tests\Feature\Media;

use App\Jobs\ProcessWebhookJob;
use App\Models\Message;
use App\Models\Team;
use App\Models\WebhookPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_image_webhook_downloads_and_links_media()
    {
        Storage::fake('public');
        Http::fake([
            'graph.facebook.com/*/12345' => Http::response(['url' => 'http://media.url/file', 'mime_type' => 'image/jpeg'], 200),
            'http://media.url/file' => Http::response('fake-image-content', 200),
        ]);

        $team = Team::factory()->create([
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_access_token' => 'valid-token'
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'from' => '919876543210',
                                        'id' => 'wamid.HBgMMTIzNDU=',
                                        'timestamp' => time(),
                                        'type' => 'image',
                                        'image' => [
                                            'mime_type' => 'image/jpeg',
                                            'sha' => 'sha123',
                                            'id' => '12345',
                                            'caption' => 'Check this out'
                                        ]
                                    ]
                                ],
                                'contacts' => [['profile' => ['name' => 'Alice']]]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $payloadRecord = WebhookPayload::create([
            'payload' => json_encode($payload),
            'status' => 'pending'
        ]);

        // Run Job
        (new ProcessWebhookJob($payloadRecord->id))->handle();

        // Assertions
        $this->assertDatabaseHas('messages', [
            'type' => 'image',
            'caption' => 'Check this out',
            'media_id' => '12345',
            'media_type' => 'image/jpeg'
        ]);

        $message = Message::first();
        $this->assertNotNull($message->media_url);

        // Verify file exists
        Storage::disk('public')->assertExists($message->media_url);
    }
}
