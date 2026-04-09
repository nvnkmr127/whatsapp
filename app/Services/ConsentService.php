<?php

namespace App\Services;

use App\Models\ConsentRegistry;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsentService
{
    /**
     * Opt-in a contact.
     *
     * @param  string  $source  Source of opt-in (e.g., 'API', 'MANUAL_ADMIN', 'START_KEYWORD')
     * @param  string|null  $notes  Additional notes for audit trail
     * @param  string|null  $proofUrl  URL to consent proof document
     * @param  int  $expiryMonths  Consent validity period (default: 24 months for GDPR)
     * @return bool True if opt-in was successful, false if already opted-in or blocked
     */
    public function optIn(Contact $contact, $source = 'MANUAL', $notes = null, $proofUrl = null, $expiryMonths = 24)
    {
        // Always log the attempt for audit trail
        $this->logAction($contact, 'OPT_IN_ATTEMPT', $source, $notes, $proofUrl);

        if ($contact->opt_in_status === 'opted_in') {
            \Illuminate\Support\Facades\Log::info('Contact already opted-in', ['contact_id' => $contact->id]);

            return false;
        }

        // Check if previously opted-out manually by admin (compliance flag)
        if ($contact->opt_in_status === 'opted_out' && $source !== 'MANUAL_ADMIN') {
            // Meta Rule: Customer sending START keyword MUST override previous opt-outs.
            if ($source === 'START_KEYWORD') {
                \Illuminate\Support\Facades\Log::info('Customer START keyword overriding previous opt-out state.', [
                    'contact_id' => $contact->id,
                ]);
            } else {
                $lastOptOut = \App\Models\ConsentLog::where('contact_id', $contact->id)
                    ->where('action', 'OPT_OUT')
                    ->latest()
                    ->first();

                if ($lastOptOut && $lastOptOut->source === 'MANUAL_ADMIN') {
                    \Illuminate\Support\Facades\Log::warning('Cannot auto-opt-in contact with admin opt-out', [
                        'contact_id' => $contact->id,
                    ]);

                    return false;
                }
            }
        }

        DB::transaction(function () use ($contact, $source, $notes, $proofUrl, $expiryMonths) {
            // Preserve original opt-in source and timestamp if re-opting in
            $originalSource = $contact->opt_in_source;
            $originalTimestamp = $contact->opt_in_at;
            $expiresAt = now()->addMonths($expiryMonths);

            $contact->update([
                'opt_in_status' => 'opted_in',
                'opt_in_source' => $originalSource ?? $source,
                'opt_in_at' => $originalTimestamp ?? now(),
                'opt_in_expires_at' => $expiresAt,
            ]);

            // ── Dual-write to immutable Consent Registry ──────────────────────
            // This is the GDPR-authoritative record. Even if the contacts table
            // is overwritten by a backup restore, this registry entry survives.
            $this->writeRegistry($contact, 'OPT_IN', $source, $notes, $proofUrl, $expiresAt);

            // Append-only audit log (legacy — kept for BI queries)
            $this->logAction($contact, 'OPT_IN', $source, $notes, $proofUrl);
        });

        return true;
    }

    /**
     * Opt-out a contact (STOP).
     *
     * @param  string  $source  Source of opt-out (e.g., 'STOP_KEYWORD', 'MANUAL_ADMIN')
     * @param  string|null  $notes  Additional notes for audit trail
     * @param  string|null  $proofUrl  URL to consent proof document
     * @return bool True if opt-out was successful, false if already opted-out
     */
    public function optOut(Contact $contact, $source = 'STOP_KEYWORD', $notes = null, $proofUrl = null)
    {
        // Always log the attempt
        $this->logAction($contact, 'OPT_OUT_ATTEMPT', $source, $notes, $proofUrl);

        if ($contact->opt_in_status === 'opted_out') {
            \Illuminate\Support\Facades\Log::info('Contact already opted-out', ['contact_id' => $contact->id]);

            return false;
        }

        DB::transaction(function () use ($contact, $source, $notes, $proofUrl) {
            $contact->update([
                'opt_in_status' => 'opted_out',
            ]);

            // ── CRITICAL: Dual-write to immutable Consent Registry ────────────
            // This opt-out MUST survive any future backup restore.
            // BackupService::rehydrateConsentFromRegistry() will re-apply
            // this entry to the contacts table after every restore, even if
            // the restore SQL would have overwritten opt_in_status back to 'opted_in'.
            $this->writeRegistry($contact, 'OPT_OUT', $source, $notes, $proofUrl, null);

            // Append-only audit log (legacy — kept for BI queries)
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
            \Illuminate\Support\Facades\Log::warning('Consent expired for contact', [
                'contact_id' => $contact->id,
                'expired_at' => $contact->opt_in_expires_at,
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
                    'error' => 'Contact opted out',
                ]);

            \Illuminate\Support\Facades\Log::info('Cancelled pending campaign messages for opted-out contact', [
                'contact_id' => $contact->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to cancel pending messages: '.$e->getMessage());
        }
    }

    /**
     * Write an authoritative entry to the immutable Consent Registry.
     * This is the GDPR-compliant record that BackupService uses to
     * re-apply consent state after a restore.
     */
    protected function writeRegistry(
        Contact $contact,
        string $action,
        ?string $source,
        ?string $notes,
        ?string $proofUrl,
        ?\Carbon\Carbon $expiresAt
    ): void {
        try {
            ConsentRegistry::create([
                'team_id' => $contact->team_id,
                'contact_id' => $contact->id,
                'phone_number' => $contact->phone_number,
                'action' => $action,
                'source' => $source,
                'channel' => 'whatsapp',
                'notes' => $notes,
                'proof_url' => $proofUrl,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'expires_at' => $expiresAt,
                'recorded_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Registry write failure is CRITICAL — log loudly but do not swallow.
            // The consent action itself succeeded; we still need to alarm on this.
            Log::critical('ConsentRegistry write failed — GDPR compliance at risk!', [
                'contact_id' => $contact->id,
                'phone_number' => $contact->phone_number,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            // Re-throw: a failed registry write should abort the consent transaction.
            throw $e;
        }
    }

    /**
     * Rehydrate a contact's consent status from the Consent Registry.
     *
     * Called by BackupService AFTER every tenant restore to ensure that
     * opt-outs recorded AFTER the backup timestamp are not silently reverted.
     *
     * Strategy: "latest registry entry wins" — if the registry says OPT_OUT
     * but the restored contacts table says opted_in, the registry takes precedence.
     */
    public function rehydrateContactFromRegistry(Contact $contact): void
    {
        $latest = ConsentRegistry::getLatestAuthoritativeState(
            $contact->team_id,
            $contact->phone_number
        );

        if (! $latest) {
            // No authoritative registry entry — nothing to enforce.
            return;
        }

        $registryStatus = $latest->action === 'OPT_OUT' ? 'opted_out' : 'opted_in';

        if ($contact->opt_in_status !== $registryStatus) {
            Log::warning('ConsentRegistry mismatch after restore — re-applying registry decision', [
                'contact_id' => $contact->id,
                'phone_number' => $contact->phone_number,
                'contacts_said' => $contact->opt_in_status,
                'registry_says' => $registryStatus,
                'registry_entry' => $latest->id,
                'registry_created' => $latest->created_at,
            ]);

            // Use DB update to bypass Eloquent booted hooks (version tracking etc.)
            // but WITHOUT touching registry — contacts table is a cache here.
            \Illuminate\Support\Facades\DB::table('contacts')
                ->where('id', $contact->id)
                ->update([
                    'opt_in_status' => $registryStatus,
                    'updated_at' => now(),
                ]);

            // Append a registry audit record noting the correction
            ConsentRegistry::create([
                'team_id' => $contact->team_id,
                'contact_id' => $contact->id,
                'phone_number' => $contact->phone_number,
                'action' => $latest->action,
                'source' => 'RESTORE_REHYDRATION',
                'channel' => 'system',
                'notes' => "Auto-corrected after backup restore. Registry entry #{$latest->id} ({$latest->created_at}) took precedence over restored contacts.opt_in_status='{$contact->opt_in_status}'.",
                'ip_address' => null,
                'recorded_at' => now(),
            ]);
        }
    }

    /**
     * Rehydrate ALL contacts for a team after a restore.
     * Called by BackupService::restoreTenant() as the final step.
     */
    public function rehydrateTeamFromRegistry(int $teamId): void
    {
        // Find all phone numbers that have OPT_OUT as their latest state
        $optedOutPhones = ConsentRegistry::latestOptOuts($teamId)
            ->pluck('phone_number')
            ->toArray();

        if (empty($optedOutPhones)) {
            Log::info('ConsentRegistry rehydration: no override needed', ['team_id' => $teamId]);

            return;
        }

        Log::warning('ConsentRegistry rehydration: enforcing opt-outs that were in registry but may have been overwritten by restore', [
            'team_id' => $teamId,
            'count' => count($optedOutPhones),
        ]);

        // Force all these contacts back to opted_out — their STOP is permanent.
        $affected = \Illuminate\Support\Facades\DB::table('contacts')
            ->where('team_id', $teamId)
            ->whereIn('phone_number', $optedOutPhones)
            ->where('opt_in_status', '!=', 'opted_out') // Only update mismatches
            ->update([
                'opt_in_status' => 'opted_out',
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            Log::warning("ConsentRegistry rehydration corrected {$affected} contact(s) after restore", [
                'team_id' => $teamId,
                'affected' => $affected,
            ]);

            // Bulk cancel any pending outbound messages for these re-blocked contacts
            \App\Models\CampaignSnapshotContact::whereIn('phone_number', $optedOutPhones)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'error' => 'Contact opt-out re-enforced after backup restore (ConsentRegistry)',
                ]);
        }
    }

    protected function logAction(Contact $contact, $action, $source, $notes, $proofUrl = null)
    {
        \App\Models\ConsentLog::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'action' => strtoupper($action),
            'source' => $source,
            'notes' => $notes,
            'proof_url' => $proofUrl,
            'ip_address' => request()->ip(),
        ]);
    }
}
