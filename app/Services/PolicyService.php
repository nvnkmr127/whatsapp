<?php

namespace App\Services;

use App\Models\Contact;
use Carbon\Carbon;

class PolicyService
{
    /**
     * Check if the 24-hour Customer Service Window is active.
     */
    public function isSessionActive(Contact $contact): bool
    {
        if (!$contact->last_interaction_at) {
            return false;
        }

        return $contact->last_interaction_at->gt(Carbon::now()->subHours(24));
    }

    /**
     * Determine if we can send a free-form text message.
     */
    public function canSendFreeMessage(Contact $contact): bool
    {
        // Global Block: If user explicitly opted out (STOP), we should respect that even for free messages?
        // Yes, ensuring strict compliance
        if ($contact->opt_in_status === 'opted_out') {
            return false;
        }

        return $this->isSessionActive($contact);
    }

    /**
     * Check if we can send a Template Message.
     */
    public function canSendTemplate(Contact $contact, string $category): bool
    {
        // Global Block
        if ($contact->opt_in_status === 'opted_out') {
            return false;
        }

        // Marketing requires strictly 'opted_in'
        if (strtoupper($category) === 'MARKETING') {
            return $contact->opt_in_status === 'opted_in';
        }

        // Utility / Authentication / Service allow implicit consent (status != opted_out)
        // So just by not being opted_out, they are allowed.
        return true;
    }
}
