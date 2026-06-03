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
        Log::debug("PushJob: started. messageId={$this->messageId} type={$this->type} conversationId={$this->conversationId}");

        $message = $this->messageId ? Message::with(['contact', 'conversation', 'team'])->find($this->messageId) : null;
        $conversation = $this->conversationId
            ? \App\Models\Conversation::with(['contact', 'team'])->find($this->conversationId)
            : $message?->conversation;

        if (!$conversation) {
            Log::warning("PushJob: aborted — conversation not found. messageId={$this->messageId}");
            return;
        }

        if ($message && $message->direction === 'outbound' && $this->type === 'new_message') {
            Log::debug("PushJob: skipped — outbound message #{$this->messageId}");
            return;
        }

        $team = $conversation->team;
        $contact = $conversation->contact;

        // Determine targets
        $targetUserIds = [];
        $assignedToId = $conversation->getRawOriginal('assigned_to') ?: $conversation->assigned_to;
        if ($assignedToId) {
            $targetUserIds[] = is_object($assignedToId) ? $assignedToId->id : (int) $assignedToId;
        } else {
            $targetUserIds = $team->users()
                ->wherePivot('receives_tickets', true)
                ->pluck('users.id')
                ->toArray();
        }

        Log::debug("PushJob: target user IDs resolved.", ['ids' => $targetUserIds, 'assigned_to' => $assignedToId]);

        if (empty($targetUserIds)) {
            Log::warning("PushJob: aborted — no target users. conversationId={$conversation->id} team={$team?->id}");
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
            'contact_name' => $contact->name ?: $contact->phone_number,
        ];

        $users = User::with('fcmTokens')->whereIn('id', $targetUserIds)->get();

        Log::debug("PushJob: sending to " . $users->count() . " users.", [
            'user_ids' => $users->pluck('id'),
            'token_counts' => $users->mapWithKeys(fn($u) => [$u->id => $u->fcmTokens->count()]),
        ]);

        foreach ($users as $user) {
            $fcmService->sendToUser($user, $title, $body, $data);
        }

        Log::info("PushJob: done. message=#{$this->messageId} users={$users->count()}");
    }
}
