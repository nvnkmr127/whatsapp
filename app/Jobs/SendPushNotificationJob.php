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
        $this->onQueue('push_notifications');
    }

    public function handle(FcmService $fcmService): void
    {
        Log::info("[PushJob] ▶ Started. messageId={$this->messageId} type={$this->type} conversationId={$this->conversationId}");

        $message = $this->messageId ? Message::with(['contact', 'conversation', 'team'])->find($this->messageId) : null;
        $conversation = $this->conversationId
            ? \App\Models\Conversation::with(['contact', 'team'])->find($this->conversationId)
            : $message?->conversation;

        if (!$conversation) {
            Log::warning("[PushJob] ❌ Aborted — conversation not found. messageId={$this->messageId}");
            return;
        }

        Log::info("[PushJob] ✅ Conversation found: id={$conversation->id}");

        if ($message && $message->direction === 'outbound' && $this->type === 'new_message') {
            Log::info("[PushJob] ⏭ Skipped — outbound message #{$this->messageId}");
            return;
        }

        $team = $conversation->team;
        $contact = $conversation->contact;

        if (!$team) {
            Log::warning("[PushJob] ❌ Aborted — team not found for conversation {$conversation->id}");
            return;
        }

        if (!$contact) {
            Log::warning("[PushJob] ❌ Aborted — contact not found for conversation {$conversation->id}");
            return;
        }

        // Determine targets
        $targetUserIds = [];
        $assignedToId = $conversation->getRawOriginal('assigned_to') ?: $conversation->assigned_to;
        if ($assignedToId) {
            $targetUserIds[] = is_object($assignedToId) ? $assignedToId->id : (int) $assignedToId;
            Log::info("[PushJob] Target: assigned user id={$assignedToId}");
        } else {
            $targetUserIds = $team->users()
                ->wherePivot('receives_tickets', true)
                ->pluck('users.id')
                ->toArray();
            Log::info("[PushJob] Target: team members with receives_tickets=true", ['ids' => $targetUserIds, 'team' => $team->id]);
        }

        if (empty($targetUserIds)) {
            // ⚠️ FALLBACK: send to ALL team members if no users have receives_tickets=true
            // This ensures someone always gets notified, even if routing isn't configured.
            $allTeamUserIds = $team->users()->pluck('users.id')->toArray();
            Log::warning("[PushJob] ⚠️ No receives_tickets users found. Falling back to ALL team members.", [
                'conversationId' => $conversation->id,
                'teamId' => $team->id,
                'allTeamUsers' => $allTeamUserIds,
            ]);
            $targetUserIds = $allTeamUserIds;
        }

        if (empty($targetUserIds)) {
            Log::warning("[PushJob] ❌ Aborted — no target users at all. conversationId={$conversation->id} team={$team->id}");
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

        Log::info("[PushJob] Sending to {$users->count()} user(s).", [
            'user_ids' => $users->pluck('id'),
            'token_counts' => $users->mapWithKeys(fn($u) => [$u->id => $u->fcmTokens->count()]),
        ]);

        foreach ($users as $user) {
            $tokenCount = $user->fcmTokens->count();
            if ($tokenCount === 0) {
                Log::warning("[PushJob] ⚠️ User {$user->id} ({$user->email}) has NO FCM tokens — notification skipped for this user.");
                continue;
            }
            Log::info("[PushJob] Sending to user {$user->id} ({$user->email}) via {$tokenCount} token(s). title='{$title}'");
            $fcmService->sendToUser($user, $title, $body, $data);
        }

        Log::info("[PushJob] ✅ Done. message=#{$this->messageId} users={$users->count()}");
    }
}
