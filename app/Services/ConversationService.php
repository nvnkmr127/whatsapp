<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ConversationService
{
    /**
     * TTL for the lock in seconds.
     */
    const LOCK_TTL = 30;

    /**
     * Try to acquire a lock for a conversation.
     *
     * @return array ['success' => bool, 'owner' => int|null, 'expires_in' => int]
     */
    public function acquireLock(int $conversationId, int $userId): array
    {
        $key = "conversation_lock:{$conversationId}";
        $currentOwner = Redis::get($key);

        // If locked by someone else
        if ($currentOwner && (int) $currentOwner !== $userId) {
            return [
                'success' => false,
                'owner' => (int) $currentOwner,
                'expires_in' => Redis::ttl($key),
            ];
        }

        // Acquire or Refresh lock
        Redis::setex($key, self::LOCK_TTL, $userId);

        return [
            'success' => true,
            'owner' => $userId,
            'expires_in' => self::LOCK_TTL,
        ];
    }

    /**
     * Force release a lock.
     */
    public function releaseLock(int $conversationId, int $userId): void
    {
        $key = "conversation_lock:{$conversationId}";
        $currentOwner = Redis::get($key);

        // Only owner can release, unless it's a force unlock (which we handle via acquire with overwrite if needed,
        // but for polite release, check owner)
        if ($currentOwner && (int) $currentOwner === $userId) {
            Redis::del($key);
        }
    }

    /**
     * Force take over a lock (break existing).
     */
    public function forceTakeOver(int $conversationId, int $userId): void
    {
        $key = "conversation_lock:{$conversationId}";
        Redis::setex($key, self::LOCK_TTL, $userId);
    }

    /**
     * Get current lock status.
     */
    public function getLockStatus(int $conversationId): ?array
    {
        $key = "conversation_lock:{$conversationId}";
        $owner = Redis::get($key);

        if (! $owner) {
            return null;
        }

        return [
            'owner' => (int) $owner,
            'expires_in' => Redis::ttl($key),
        ];
    }

    /**
     * Get the active conversation for a contact, or create a new one.
     */
    public function ensureActiveConversation(Contact $contact): Conversation
    {
        $active = $contact->activeConversation;

        if ($active) {
            return $active;
        }

        // Create New
        $assignedUser = null;
        if (! $contact->assigned_to) {
            try {
                $assignedUser = app(\App\Services\AssignmentService::class)->assign($contact);
            } catch (\Exception $e) {
                Log::warning("Auto-assignment skipped for contact {$contact->id}: " . $e->getMessage());
            }
        }

        $conversation = Conversation::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'assigned_to' => $assignedUser?->id ?? $contact->assigned_to,
            'status' => 'new',
            'last_message_at' => now(),
        ]);

        try {
            \App\Events\ConversationOpened::dispatch($conversation);
        } catch (\Exception $e) {
            Log::warning("ConversationOpened broadcast failed for conversation {$conversation->id}, but it was created successfully: ".$e->getMessage());
        }

        return $conversation;
    }

    /**
     * Handle incoming Customer Message logic:
     * - Update last_message_at
     * - Re-open if closed (business logic dependent - assume new conversation handles this via ensureActive)
     * - Set status to 'open' if it was 'waiting_reply'? Or maybe stay 'open'.
     */
    public function handleIncomingMessage(Conversation $conversation)
    {
        $conversation->update([
            'last_message_at' => now(),
            'status' => 'open', // Moves from 'new' or 'waiting_reply' to 'open' (needs agent attention)
        ]);
    }

    /**
     * Handle Outbound Agent Message logic:
     * - Set status to 'waiting_reply'
     */
    public function handleOutboundMessage(Conversation $conversation, bool $isBot = false)
    {
        $conversation->update([
            'last_message_at' => now(),
            'status' => 'waiting_reply',
        ]);

        if (! $isBot) {
            (new BotHandoffService)->handleAgentActivity($conversation->contact);
        }
    }

    /**
     * Close the conversation.
     */
    public function close(Conversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        (new BotHandoffService)->resume($conversation->contact);

        try {
            \App\Events\ConversationClosed::dispatch($conversation);
        } catch (\Exception $e) {
            Log::warning("ConversationClosed broadcast failed for conversation {$conversation->id}, but it was closed successfully: ".$e->getMessage());
        }

        try {
            app(\App\Services\CsatService::class)->sendSurvey($conversation);
        } catch (\Exception $e) {
            Log::warning("CSAT survey dispatch skipped for conversation {$conversation->id}: " . $e->getMessage());
        }
    }
}
