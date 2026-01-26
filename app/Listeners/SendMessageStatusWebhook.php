<?php

namespace App\Listeners;

use App\Events\MessageStatusUpdated;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMessageStatusWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function __construct(protected WebhookService $webhookService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(MessageStatusUpdated $event): void
    {
        $message = $event->message;
        $team = $message->team;

        if (!$team) {
            return;
        }

        $data = [
            'id' => $message->id,
            'whatsapp_message_id' => $message->whatsapp_message_id,
            'contact' => [
                'id' => $message->contact_id,
                'phone_number' => $message->contact->phone_number ?? null,
            ],
            'status' => $message->status,
            'delivered_at' => $message->delivered_at?->toIso8601String(),
            'read_at' => $message->read_at?->toIso8601String(),
            'error_message' => $message->error_message,
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $this->webhookService->dispatch($team->id, 'message.status_updated', $data);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch message.status_updated webhook: " . $e->getMessage());
        }
    }
}
