<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\ContactStateManager;
use App\Models\ContactEvent;
use Illuminate\Support\Facades\Cache;

class UpdateContactStateOnMessageReceived
{
    protected $stateManager;

    /**
     * Create the event listener.
     */
    public function __construct(ContactStateManager $stateManager)
    {
        $this->stateManager = $stateManager;
    }

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $message = $event->message;
        $idempotencyKey = "message_received:{$message->id}";

        // Check if already processed
        if (Cache::has($idempotencyKey)) {
            return;
        }

        // Update contact state
        $this->stateManager->onMessageReceived($message);

        // Record event for audit trail
        ContactEvent::create([
            'team_id' => $message->contact->team_id,
            'contact_id' => $message->contact_id,
            'event_type' => 'MessageReceived',
            'event_data' => [
                'message_id' => $message->id,
                'timestamp' => $message->created_at,
            ],
            'occurred_at' => $message->created_at,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Mark as processed
        Cache::put($idempotencyKey, true, 3600);
    }
}
