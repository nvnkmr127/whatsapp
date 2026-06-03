<?php

namespace App\Listeners;

use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPushNotificationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(protected FcmService $fcmService) {}

    /**
     * Handle the event.
     * Message push notifications are handled by SendPushNotificationJob (dispatched from PersistMessageJob)
     * which applies assigned_to / receives_tickets targeting logic.
     */
    public function handle(object $event): void
    {
        if ($event instanceof \App\Events\CallOffered) {
            $this->handleCallOffered($event);
        }
    }

    /**
     * Handle call offered event.
     */
    protected function handleCallOffered(\App\Events\CallOffered $event): void
    {
        $call = $event->call;

        // Skip outbound calls
        if ($call->direction !== 'inbound') {
            return;
        }

        $team = $call->team;
        if (! $team) {
            return;
        }

        // Get all users in the team (owner + members)
        $users = $team->allUsers();

        foreach ($users as $user) {
            // Don't send if the user has no FCM tokens
            if ($user->fcmTokens->isEmpty()) {
                continue;
            }

            $contactName = $call->contact?->name ?? $call->from_number ?? 'Unknown';
            $title = 'Incoming WhatsApp Call';
            $body = 'from ' . $contactName;

            $this->fcmService->sendToUser($user, $title, $body, [
                'type' => 'call_incoming',
                'call_id' => (string) $call->call_id,
                'conversation_id' => (string) $call->conversation_id,
                'team_id' => (string) $call->team_id,
                'contact_name' => $contactName,
            ]);
        }

        Log::info("Push Notification dispatched for incoming call {$call->call_id}");
    }
}
