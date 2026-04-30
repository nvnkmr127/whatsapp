<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $messageId = null,
        public string $type = 'new_message',
        public ?int $conversationId = null
    ) {
        $this->onQueue('notifications');
    }

    public function handle(FcmService $fcmService): void
    {
        $message = $this->messageId ? Message::with(['contact', 'conversation', 'team'])->find($this->messageId) : null;
        $conversation = $this->conversationId 
            ? \App\Models\Conversation::with(['contact', 'team'])->find($this->conversationId)
            : $message?->conversation;

        if (!$conversation) {
            return;
        }

        if ($message && $message->direction === 'outbound' && $this->type === 'new_message') {
            return;
        }

        $team = $conversation->team;
        $contact = $conversation->contact;

        // Determine targets
        $targetUserIds = [];
        if ($conversation->assigned_to) {
            $targetUserIds[] = $conversation->assigned_to;
        } else {
            // Notify all agents who receive tickets
            $targetUserIds = $team->users()
                ->wherePivot('receives_tickets', true)
                ->pluck('users.id')
                ->toArray();
        }

        if (empty($targetUserIds)) {
            return;
        }

        $title = $contact->name ?: $contact->phone_number;
        $body = $message ? ($message->content ?: "Sent a {$message->type}") : "New activity in chat";

        if ($this->type === 'sla_breach') {
            $title = "🚨 SLA BREACHED";
            $body = "Conversation with {$contact->name} exceeded SLA!";
        } elseif ($this->type === 'sla_warning') {
            $title = "⚠️ SLA WARNING";
            $body = "Conversation with {$contact->name} is about to breach.";
        }
        
        $data = [
            'type' => $this->type,
            'conversation_id' => (string) $conversation->id,
            'contact_id' => (string) $contact->id,
            'team_id' => (string) $team->id,
            'message_id' => (string) ($message->id ?? ''),
        ];

        $users = User::whereIn('id', $targetUserIds)->get();

        foreach ($users as $user) {
            $fcmService->sendToUser($user, $title, $body, $data);
        }

        Log::info("Push notification sent for message #{$message->id} to " . count($users) . " users.");
    }
}
