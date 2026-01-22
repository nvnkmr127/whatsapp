<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactStateManager
{
    /**
     * Calculate engagement score (0-100).
     */
    public function calculateEngagementScore(Contact $contact): int
    {
        $score = 0;

        // Recency (0-30 points)
        $daysSinceLastMessage = $contact->last_interaction_at
            ? now()->diffInDays($contact->last_interaction_at)
            : 999;

        if ($daysSinceLastMessage <= 1) {
            $score += 30;
        } elseif ($daysSinceLastMessage <= 7) {
            $score += 20;
        } elseif ($daysSinceLastMessage <= 30) {
            $score += 10;
        }

        // Frequency (0-30 points)
        $messagesLast30Days = Message::where('contact_id', $contact->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $score += min($messagesLast30Days * 3, 30);

        // Conversation activity (0-20 points)
        $activeConversations = $contact->conversations()
            ->whereIn('status', ['open', 'waiting_reply'])
            ->count();

        $score += min($activeConversations * 10, 20);

        // Consent status (0-10 points)
        if ($contact->opt_in_status === 'opted_in') {
            $score += 10;
        }

        // Read rate (0-10 points)
        $readRate = $this->calculateReadRate($contact);
        $score += $readRate * 10;

        return min($score, 100);
    }

    /**
     * Calculate read rate for recent messages.
     */
    protected function calculateReadRate(Contact $contact): float
    {
        $recentMessages = Message::where('contact_id', $contact->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($recentMessages->isEmpty()) {
            return 0;
        }

        $readCount = $recentMessages->where('status', 'read')->count();

        return $readCount / $recentMessages->count();
    }

    /**
     * Calculate lifecycle state.
     */
    public function calculateLifecycleState(Contact $contact): string
    {
        $daysSinceLastMessage = $contact->last_interaction_at
            ? now()->diffInDays($contact->last_interaction_at)
            : 999;

        $messageCount = $contact->message_count ?? 0;

        // New: No messages yet
        if ($messageCount === 0) {
            return 'new';
        }

        // Churned: No activity 90+ days
        if ($daysSinceLastMessage >= 90) {
            return 'churned';
        }

        // Dormant: No activity 30-89 days
        if ($daysSinceLastMessage >= 30) {
            return 'dormant';
        }

        // Engaged: 5+ messages in last 30 days
        $recentMessages = Message::where('contact_id', $contact->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($recentMessages >= 5) {
            return 'engaged';
        }

        // Active: Default for recent activity
        return 'active';
    }

    /**
     * Calculate average response time in seconds.
     */
    public function calculateAvgResponseTime(Contact $contact): ?int
    {
        $responseTimes = DB::table('messages as customer_msg')
            ->join('messages as agent_msg', function ($join) {
                $join->on('agent_msg.contact_id', '=', 'customer_msg.contact_id')
                    ->on('agent_msg.created_at', '>', 'customer_msg.created_at')
                    ->where('agent_msg.direction', 'outbound');
            })
            ->where('customer_msg.contact_id', $contact->id)
            ->where('customer_msg.direction', 'inbound')
            ->select(DB::raw('MIN(TIMESTAMPDIFF(SECOND, customer_msg.created_at, agent_msg.created_at)) as response_time'))
            ->groupBy('customer_msg.id')
            ->pluck('response_time');

        return $responseTimes->isEmpty() ? null : (int) $responseTimes->avg();
    }

    /**
     * Check if contact is within 24h window.
     */
    public function isWithin24hWindow(Contact $contact): bool
    {
        if (!$contact->last_customer_message_at) {
            return false;
        }

        $hoursSinceLastMessage = now()->diffInHours($contact->last_customer_message_at);

        return $hoursSinceLastMessage < 24;
    }

    /**
     * Check if contact has pending reply.
     */
    public function hasPendingReply(Contact $contact): bool
    {
        $lastMessage = $contact->messages()->latest()->first();

        if (!$lastMessage || $lastMessage->direction !== 'inbound') {
            return false;
        }

        // Check if there's an agent reply after this message
        $hasReply = Message::where('contact_id', $contact->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>', $lastMessage->created_at)
            ->exists();

        return !$hasReply;
    }

    /**
     * Update engagement score for contact.
     */
    public function updateEngagementScore(Contact $contact): void
    {
        $score = $this->calculateEngagementScore($contact);
        $contact->update(['engagement_score' => $score]);
    }

    /**
     * Update lifecycle state for contact.
     */
    public function updateLifecycleState(Contact $contact): void
    {
        $newState = $this->calculateLifecycleState($contact);

        if ($contact->lifecycle_state !== $newState) {
            $oldState = $contact->lifecycle_state;
            $contact->update(['lifecycle_state' => $newState]);

            Log::info("Contact lifecycle changed", [
                'contact_id' => $contact->id,
                'old_state' => $oldState,
                'new_state' => $newState,
            ]);

            // Fire state transition event
            event(new \App\Events\ContactLifecycleChanged($contact, $oldState, $newState));
        }
    }

    /**
     * Update all derived fields for contact.
     */
    public function updateDerivedFields(Contact $contact): void
    {
        $contact->update([
            'engagement_score' => $this->calculateEngagementScore($contact),
            'lifecycle_state' => $this->calculateLifecycleState($contact),
            'avg_response_time' => $this->calculateAvgResponseTime($contact),
            'is_within_24h_window' => $this->isWithin24hWindow($contact),
            'has_pending_reply' => $this->hasPendingReply($contact),
            'days_since_last_message' => $contact->last_interaction_at
                ? now()->diffInDays($contact->last_interaction_at)
                : null,
        ]);
    }

    /**
     * Handle message received event.
     */
    public function onMessageReceived(Message $message): void
    {
        $contact = $message->contact;

        DB::transaction(function () use ($contact, $message) {
            // Update timestamps
            $this->updateTimestampIfNewer($contact, 'last_interaction_at', $message->created_at);
            $this->updateTimestampIfNewer($contact, 'last_customer_message_at', $message->created_at);

            // Increment counters
            $contact->increment('inbound_message_count');
            $contact->increment('message_count');

            // Update flags
            $contact->update([
                'has_pending_reply' => true,
                'is_within_24h_window' => true,
            ]);

            // Recalculate derived fields
            $this->updateEngagementScore($contact);
            $this->updateLifecycleState($contact);
        });
    }

    /**
     * Handle message sent event.
     */
    public function onMessageSent(Message $message): void
    {
        $contact = $message->contact;

        DB::transaction(function () use ($contact, $message) {
            // Update timestamps
            $this->updateTimestampIfNewer($contact, 'last_interaction_at', $message->created_at);
            $this->updateTimestampIfNewer($contact, 'last_agent_message_at', $message->created_at);

            // Increment counters
            $contact->increment('outbound_message_count');
            $contact->increment('message_count');

            // Update flags
            $contact->update([
                'has_pending_reply' => false,
            ]);

            // Update campaign attribution
            if ($message->attributed_campaign_id) {
                $contact->update(['last_campaign_id' => $message->attributed_campaign_id]);
            }

            // Recalculate derived fields
            $this->updateEngagementScore($contact);
        });
    }

    /**
     * Update timestamp only if new timestamp is newer.
     */
    protected function updateTimestampIfNewer(Contact $contact, string $field, $newTimestamp): void
    {
        Contact::where('id', $contact->id)
            ->where(function ($query) use ($field, $newTimestamp) {
                $query->whereNull($field)
                    ->orWhere($field, '<', $newTimestamp);
            })
            ->update([$field => $newTimestamp]);
    }
}
