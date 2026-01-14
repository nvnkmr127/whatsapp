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
        $team = $message->team;

        if (!$team) {
            return;
        }

        // Use the new WebhookService
        $webhookService = new \App\Services\WebhookService();

        $data = [
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
        ];

        $webhookService->dispatch($team->id, 'message.received', $data);
    }
}
