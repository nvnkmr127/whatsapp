<?php

namespace App\Listeners;

use App\Events\MessageReceived;
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
     */
    public function handle(object $event): void
    {
        if ($event instanceof MessageReceived) {
            $this->handleMessageReceived($event);
        } elseif ($event instanceof \App\Events\CallOffered) {
            $this->handleCallOffered($event);
        }
    }

    /**
     * Handle message received event.
     */
    protected function handleMessageReceived(MessageReceived $event): void
    {
        $message = $event->message;

        // Skip outbound messages (already handled by Sent event if needed)
        if ($message->direction !== 'inbound') {
            return;
        }

        $team = $message->team;
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

            // Skip if user is actively viewing this specific conversation (Web or App)
            if ($user->isActiveInConversation($message->conversation_id)) {
                Log::debug("Push skipped for User {$user->id}: Active in conversation {$message->conversation_id}");

                continue;
            }

            $title = 'New Message from '.($message->contact->name ?? $message->contact->phone ?? 'Unknown');
            $body = $message->content ?: ($message->type === 'image' ? '📷 Image' : '📎 Attachment');

            $this->fcmService->sendToUser($user, $title, $body, [
                'type' => 'message.inbound',
                'conversation_id' => (string) $message->conversation_id,
                'team_id' => (string) $message->team_id,
                'message_id' => (string) $message->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);
        }

        Log::info("Push Notification dispatched for message {$message->id}");
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
                'type' => 'call.incoming',
                'call_id' => (string) $call->call_id,
                'conversation_id' => (string) $call->conversation_id,
                'team_id' => (string) $call->team_id,
                'contact_name' => $contactName,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);
        }

        Log::info("Push Notification dispatched for incoming call {$call->call_id}");
    }
}
