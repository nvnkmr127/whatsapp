<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOutboundWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $message = $event->message;
        $team = $message->team; // Assumes Message belongsTo Team

        if (!$team || empty($team->outbound_webhook_url)) {
            return;
        }

        try {
            $payload = [
                'event' => 'message.received',
                'data' => [
                    'id' => $message->id,
                    'whatsapp_message_id' => $message->whatsapp_message_id,
                    'contact' => [
                        'id' => $message->contact_id,
                        'phone_number' => $message->contact->phone_number,
                        'name' => $message->contact->name,
                        'custom_attributes' => $message->contact->custom_attributes,
                    ],
                    'content' => $message->content,
                    'media_url' => $message->media_url ?? null,
                    'type' => $message->type,
                    'timestamp' => $message->created_at->toIso8601String(),
                ]
            ];

            // Send webhook with a timeout
            $response = Http::timeout(5)
                ->post($team->outbound_webhook_url, $payload);

            if ($response->failed()) {
                Log::warning("Outbound Webhook Failed for Team {$team->id}: " . $response->status());
                // Throwing exception triggers existing retry logic for queued listeners
                throw new \Exception("Webhook failed with status " . $response->status());
            }

        } catch (\Exception $e) {
            Log::error("Outbound Webhook Error for Team {$team->id}: " . $e->getMessage());
            $this->release(30); // Retry in 30 seconds
        }
    }
}
