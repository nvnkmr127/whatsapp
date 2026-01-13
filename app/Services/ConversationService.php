<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ConversationService
{
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
        Log::info("Creates new conversation for contact {$contact->id}");

        $conversation = Conversation::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'status' => 'new',
            'last_message_at' => now(),
            // 'assigned_to' could be auto-assigned implicitly via routing logic later
        ]);

        \App\Events\ConversationOpened::dispatch($conversation);

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
    public function handleOutboundMessage(Conversation $conversation)
    {
        $conversation->update([
            'last_message_at' => now(),
            'status' => 'waiting_reply',
        ]);
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

        \App\Events\ConversationClosed::dispatch($conversation);
    }
}
