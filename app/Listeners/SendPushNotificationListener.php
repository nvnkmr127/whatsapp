<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPushNotificationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(protected FcmService $fcmService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(MessageReceived $event): void
    {
        $message = $event->message;
        
        // Skip outbound messages (already handled by Sent event if needed)
        if ($message->direction !== 'inbound') {
            return;
        }

        $team = $message->team;
        if (!$team) return;

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
            
            $title = "New Message from " . ($message->contact->name ?? $message->contact->phone ?? "Unknown");
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
}
