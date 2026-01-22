<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\ContactStateManager;
use App\Models\ContactEvent;
use Illuminate\Support\Facades\Cache;

class UpdateContactStateOnMessageSent
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
    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $idempotencyKey = "message_sent:{$message->id}";

        // Check if already processed
        if (Cache::has($idempotencyKey)) {
            return;
        }

        // Update contact state
        $this->stateManager->onMessageSent($message);

        // Record event for audit trail
        ContactEvent::create([
            'team_id' => $message->contact->team_id,
            'contact_id' => $message->contact_id,
            'event_type' => 'MessageSent',
            'event_data' => [
                'message_id' => $message->id,
                'timestamp' => $message->created_at,
                'campaign_id' => $message->attributed_campaign_id,
            ],
            'occurred_at' => $message->created_at,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Mark as processed
        Cache::put($idempotencyKey, true, 3600);
    }
}
