<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ConsentService
{
    /**
     * Opt-in a contact.
     * 
     * @param Contact $contact
     * @param string $source Source of opt-in (e.g., 'API', 'MANUAL_ADMIN', 'START_KEYWORD')
     * @param string|null $notes Additional notes for audit trail
     * @param string|null $proofUrl URL to consent proof document
     * @param int $expiryMonths Consent validity period (default: 24 months for GDPR)
     * @return bool True if opt-in was successful, false if already opted-in or blocked
     */
    public function optIn(Contact $contact, $source = 'MANUAL', $notes = null, $proofUrl = null, $expiryMonths = 24)
    {
        // Always log the attempt for audit trail
        $this->logAction($contact, 'OPT_IN_ATTEMPT', $source, $notes, $proofUrl);

        if ($contact->opt_in_status === 'opted_in') {
            \Illuminate\Support\Facades\Log::info("Contact already opted-in", ['contact_id' => $contact->id]);
            return false;
        }

        // Check if previously opted-out manually by admin (compliance flag)
        if ($contact->opt_in_status === 'opted_out' && $source !== 'MANUAL_ADMIN') {
            $lastOptOut = \App\Models\ConsentLog::where('contact_id', $contact->id)
                ->where('action', 'OPT_OUT')
                ->latest()
                ->first();

            if ($lastOptOut && $lastOptOut->source === 'MANUAL_ADMIN') {
                \Illuminate\Support\Facades\Log::warning("Cannot auto-opt-in contact with admin opt-out", [
                    'contact_id' => $contact->id
                ]);
                return false;
            }
        }

        DB::transaction(function () use ($contact, $source, $notes, $proofUrl, $expiryMonths) {
            // Preserve original opt-in source and timestamp if re-opting in
            $originalSource = $contact->opt_in_source;
            $originalTimestamp = $contact->opt_in_at;

            $contact->update([
                'opt_in_status' => 'opted_in',
                'opt_in_source' => $originalSource ?? $source,
                'opt_in_at' => $originalTimestamp ?? now(),
                'opt_in_expires_at' => now()->addMonths($expiryMonths),
            ]);

            $this->logAction($contact, 'OPT_IN', $source, $notes, $proofUrl);
        });

        return true;
    }

    /**
     * Opt-out a contact (STOP).
     * 
     * @param Contact $contact
     * @param string $source Source of opt-out (e.g., 'STOP_KEYWORD', 'MANUAL_ADMIN')
     * @param string|null $notes Additional notes for audit trail
     * @param string|null $proofUrl URL to consent proof document
     * @return bool True if opt-out was successful, false if already opted-out
     */
    public function optOut(Contact $contact, $source = 'STOP_KEYWORD', $notes = null, $proofUrl = null)
    {
        // Always log the attempt
        $this->logAction($contact, 'OPT_OUT_ATTEMPT', $source, $notes, $proofUrl);

        if ($contact->opt_in_status === 'opted_out') {
            \Illuminate\Support\Facades\Log::info("Contact already opted-out", ['contact_id' => $contact->id]);
            return false;
        }

        DB::transaction(function () use ($contact, $source, $notes, $proofUrl) {
            $contact->update([
                'opt_in_status' => 'opted_out',
            ]);

            $this->logAction($contact, 'OPT_OUT', $source, $notes, $proofUrl);

            // Cancel pending campaign messages
            $this->cancelPendingMessages($contact);

            \App\Events\ContactOptedOut::dispatch($contact);
        });

        return true;
    }

    /**
     * Check if we can send a MARKETING message.
     * (Utility messages might be allowed depending on 24h window, but strictly speaking opt-in is always best).
     */
    public function canSendMarketing(Contact $contact)
    {
        if ($contact->opt_in_status !== 'opted_in') {
            return false;
        }

        // Check consent expiry for GDPR compliance
        if ($contact->opt_in_expires_at && $contact->opt_in_expires_at < now()) {
            \Illuminate\Support\Facades\Log::warning("Consent expired for contact", [
                'contact_id' => $contact->id,
                'expired_at' => $contact->opt_in_expires_at
            ]);
            return false;
        }

        return true;
    }

    /**
     * Cancel pending campaign messages for opted-out contact.
     */
    protected function cancelPendingMessages(Contact $contact)
    {
        try {
            // Cancel pending messages in campaign snapshots
            \App\Models\CampaignSnapshotContact::where('phone_number', $contact->phone_number)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'error' => 'Contact opted out'
                ]);

            \Illuminate\Support\Facades\Log::info("Cancelled pending campaign messages for opted-out contact", [
                'contact_id' => $contact->id
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to cancel pending messages: " . $e->getMessage());
        }
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
