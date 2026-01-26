<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMessageSentWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function __construct(protected WebhookService $webhookService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
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
                'name' => $message->contact->name ?? null,
            ],
            'content' => $message->content,
            'media_url' => $message->media_url ?? null,
            'type' => $message->type,
            'direction' => $message->direction,
            'status' => $message->status,
            'timestamp' => $message->sent_at?->toIso8601String() ?? now()->toIso8601String(),
        ];

        try {
            $this->webhookService->dispatch($team->id, 'message.sent', $data);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch message.sent webhook: " . $e->getMessage());
        }
    }
}
