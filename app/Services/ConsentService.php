<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ConsentService
{
    /**
     * Opt-in a contact.
     */
    public function optIn(Contact $contact, $source = 'MANUAL', $notes = null, $proofUrl = null)
    {
        if ($contact->opt_in_status === 'opted_in') {
            return;
        }

        DB::transaction(function () use ($contact, $source, $notes, $proofUrl) {
            $contact->update([
                'opt_in_status' => 'opted_in',
                'opt_in_source' => $source,
                'opt_in_at' => now(),
            ]);

            $this->logAction($contact, 'OPT_IN', $source, $notes, $proofUrl);
        });
    }

    /**
     * Opt-out a contact (STOP).
     */
    public function optOut(Contact $contact, $source = 'STOP_KEYWORD', $notes = null, $proofUrl = null)
    {
        if ($contact->opt_in_status === 'opted_out') {
            return;
        }

        DB::transaction(function () use ($contact, $source, $notes, $proofUrl) {
            $contact->update([
                'opt_in_status' => 'opted_out',
            ]);

            $this->logAction($contact, 'OPT_OUT', $source, $notes, $proofUrl);

            \App\Events\ContactOptedOut::dispatch($contact);
        });
    }

    /**
     * Check if we can send a MARKETING message.
     * (Utility messages might be allowed depending on 24h window, but strictly speaking opt-in is always best).
     */
    public function canSendMarketing(Contact $contact)
    {
        return $contact->opt_in_status === 'opted_in';
    }

    protected function logAction(Contact $contact, $action, $source, $notes, $proofUrl = null)
    {
        \App\Models\ConsentLog::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'action' => $action,
            'source' => $source,
            'notes' => $notes,
            'proof_url' => $proofUrl,
            'ip_address' => request()->ip(),
        ]);
    }
}
