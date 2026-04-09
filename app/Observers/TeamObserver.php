<?php

namespace App\Observers;

use App\Models\Team;
use App\Services\OfferAuditService;
use Illuminate\Support\Facades\Log;

/**
 * TeamObserver
 * ════════════
 * Guards the three "No Trial Re-triggering" cases that touch a Team:
 *
 *  Case A – WABA disconnect / credential wipe
 *    Clearing `whatsapp_business_account_id` or `whatsapp_access_token`
 *    must NEVER reset `offer_claimed_at`. We forcibly restore the value
 *    before the UPDATE is committed.
 *
 *  Case B – Team soft/hard deletion
 *    When a team is deleted we propagate the offer claim up to the owner
 *    user (sets `users.has_claimed_offer = true`) so they cannot create
 *    a brand-new team and reclaim the offer.
 *
 *  Case C – Trial re-assignment via `subscription_status` flip
 *    Any code that sets `subscription_status = 'trial'` on a team that
 *    already claimed the offer is silently reverted. This covers the CRM
 *    `toggleOffer()` path and any accidental direct-model saves.
 */
class TeamObserver
{
    // ------------------------------------------------------------------
    // updating – fires BEFORE the SQL UPDATE runs
    // ------------------------------------------------------------------

    public function updating(Team $team): void
    {
        // ── Guard A: protect offer_claimed_at from being nulled ─────────
        // Someone is trying to clear/overwrite the claim timestamp.
        // We silently restore the original value.
        if ($team->isDirty('offer_claimed_at') && $team->getOriginal('offer_claimed_at') !== null) {
            $original = $team->getOriginal('offer_claimed_at');
            $team->offer_claimed_at = $original;

            Log::warning('TeamObserver: blocked attempt to clear offer_claimed_at', [
                'team_id' => $team->id,
                'tried_to' => $team->getDirty()['offer_claimed_at'] ?? null,
            ]);

            OfferAuditService::logClaimProtected($team, 'offer_claimed_at_cleared');
        }

        // ── Guard C: prevent re-assignment of trial to an already-claimed team
        if (
            $team->isDirty('subscription_status') &&
            $team->subscription_status === 'trial' &&
            $team->getOriginal('offer_claimed_at') !== null
        ) {
            // The team already claimed an offer — revert the status
            $team->subscription_status = $team->getOriginal('subscription_status');

            Log::warning('TeamObserver: blocked trial re-assignment on already-claimed team', [
                'team_id' => $team->id,
            ]);

            OfferAuditService::logClaimProtected($team, 'subscription_status_flip_to_trial');
        }
    }

    // ------------------------------------------------------------------
    // deleting – fires BEFORE the row is removed
    // ------------------------------------------------------------------

    public function deleting(Team $team): void
    {
        // ── Guard B: propagate claim to owner user ──────────────────────
        // If this team ever claimed the offer, mark the owner permanently
        // so they cannot get a fresh trial if they re-register.
        if ($team->offer_claimed_at !== null) {
            $owner = $team->owner ?? \App\Models\User::find($team->user_id);

            if ($owner && ! $owner->has_claimed_offer) {
                $owner->has_claimed_offer = true;
                $owner->offer_claimed_at = $team->offer_claimed_at;
                $owner->saveQuietly();

                Log::info('TeamObserver: propagated offer claim to user on team deletion', [
                    'team_id' => $team->id,
                    'user_id' => $owner->id,
                ]);
            }
        }

        // Note: identity_fingerprints rows use `nullOnDelete` for team_id,
        // so they survive the team deletion and continue blocking re-use
        // of the same domain / IP / payment method.
    }
}
