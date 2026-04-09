<?php

namespace App\Observers;

use App\Models\IdentityFingerprint;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * UserObserver
 * ════════════
 * Guards the "No Trial Re-triggering via email change" case:
 *
 *  Case D – Email address change
 *    When a user changes their email address the old domain fingerprint
 *    must NOT be removed (it stays in `identity_fingerprints` and keeps
 *    blocking that domain). We also add a fingerprint for the NEW domain
 *    so it is tracked from this point forward.
 *
 *    Critically, `has_claimed_offer` is NEVER clearable via mass-assignment;
 *    if someone tries to flip it back to false we restore it here.
 */
class UserObserver
{
    // ------------------------------------------------------------------
    // created – fires AFTER the SQL INSERT
    // ------------------------------------------------------------------

    public function created(User $user): void
    {
        // 1. Initialize Onboarding Status
        $user->onboardingStatus()->create([
            'account_created' => true,
            'last_activity_at' => now(),
        ]);

        // 2. Trigger "User Signed Up" Workflows
        app(\App\Services\WorkflowEngine::class)->trigger('user_signed_up', $user);
    }

    // ------------------------------------------------------------------
    // updating – fires BEFORE the SQL UPDATE
    // ------------------------------------------------------------------

    public function updating(User $user): void
    {
        // ── Guard D-1: protect has_claimed_offer from being unset ───────
        if ($user->isDirty('has_claimed_offer') && $user->getOriginal('has_claimed_offer') == true) {
            // Someone tried to set it to false — restore
            $user->has_claimed_offer = true;

            Log::warning('UserObserver: blocked attempt to clear has_claimed_offer', [
                'user_id' => $user->id,
            ]);
        }

        // ── Guard D-2: protect offer_claimed_at from being cleared ──────
        if ($user->isDirty('offer_claimed_at') && $user->getOriginal('offer_claimed_at') !== null) {
            $user->offer_claimed_at = $user->getOriginal('offer_claimed_at');
        }
    }

    // ------------------------------------------------------------------
    // updated – fires AFTER the SQL UPDATE commits
    // ------------------------------------------------------------------

    public function updated(User $user): void
    {
        // ── Guard D-3: on email change, add fingerprint for new domain ──
        if ($user->wasChanged('email')) {
            $this->trackEmailDomainChange($user);
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function trackEmailDomainChange(User $user): void
    {
        // Resolve the current team for this user
        $team = $user->currentTeam ?? $user->ownedTeams()->first();

        if (! $team) {
            return; // Guest / orphaned user – nothing to fingerprint
        }

        // Extract new apex domain
        $newDomain = $this->apexDomain($user->email);
        $newFingerprint = IdentityFingerprint::hash($newDomain);

        IdentityFingerprint::firstOrCreate(
            [
                'type' => IdentityFingerprint::TYPE_EMAIL_DOMAIN,
                'fingerprint' => $newFingerprint,
                'team_id' => $team->id,
            ],
            ['user_id' => $user->id]
        );

        Log::info('UserObserver: added domain fingerprint for email change', [
            'user_id' => $user->id,
            'new_domain' => $newDomain,
            'team_id' => $team->id,
        ]);
    }

    private function apexDomain(string $email): string
    {
        $host = strtolower(substr($email, strrpos($email, '@') + 1));
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $host = implode('.', array_slice($parts, -2));
        }

        return $host;
    }
}
