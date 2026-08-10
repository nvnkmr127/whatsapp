<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Models\ContactEvent;
use App\Services\ContactStateManager;
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

        // Update contact state
        try {
            $this->stateManager->onMessageReceived($message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Contact state update warning for msg #{$message->id}: " . $e->getMessage());
        }

        // Record event for audit trail idempotently
        try {
            ContactEvent::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'team_id' => $message->contact->team_id,
                    'contact_id' => $message->contact_id,
                    'event_type' => 'MessageReceived',
                    'event_data' => [
                        'message_id' => $message->id,
                        'timestamp' => $message->created_at,
                    ],
                    'occurred_at' => $message->created_at,
                ]
            );
        } catch (\Throwable $e) {
            // Ignore if duplicate event already exists in DB
            if (!str_contains($e->getMessage(), '1062') && !str_contains($e->getMessage(), 'Integrity constraint')) {
                throw $e;
            }
        }

        // Mark as processed
        Cache::put($idempotencyKey, true, 3600);
    }
}
