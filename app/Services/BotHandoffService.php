<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\AutomationRun;
use Illuminate\Support\Facades\Log;

class BotHandoffService
{
    /**
     * Pause the bot for a specific contact.
     */
    public function pause(Contact $contact, string $reason = 'manual', ?int $durationSeconds = null)
    {
        $contact->update([
            'is_bot_paused' => true,
            'bot_paused_at' => now(),
            'bot_paused_reason' => $reason,
            'bot_paused_until' => $durationSeconds ? now()->addSeconds($durationSeconds) : null,
        ]);

        // Interrupt any active runs
        AutomationRun::where('contact_id', $contact->id)
            ->whereIn('status', ['active', 'waiting_input', 'paused'])
            ->update(['status' => 'interrupted']);

        Log::info("Bot paused for contact {$contact->id}. Reason: {$reason}");
    }

    /**
     * Resume the bot for a specific contact.
     */
    public function resume(Contact $contact)
    {
        $contact->update([
            'is_bot_paused' => false,
            'bot_paused_at' => null,
            'bot_paused_reason' => null,
            'bot_paused_until' => null,
        ]);

        Log::info("Bot resumed for contact {$contact->id}");
    }

    /**
     * Check if the bot should process messages for this contact.
     */
    public function shouldProcess(Contact $contact): bool
    {
        // 1. Check if an agent is explicitly assigned
        if ($contact->assigned_to) {
            return false;
        }

        // 2. Check manual pause status
        if ($contact->is_bot_paused) {
            // Check if temporary pause has expired
            if ($contact->bot_paused_until && $contact->bot_paused_until <= now()) {
                $this->resume($contact);
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Handle manual agent activity to automatically pause bot.
     */
    public function handleAgentActivity(Contact $contact)
    {
        // If an agent interacts, pause bot for 24 hours by default
        // This ensures the bot doesn't interfere during active human conversation
        if ($this->shouldProcess($contact)) {
            $this->pause($contact, 'agent_override', 86400);
        }
    }
}
