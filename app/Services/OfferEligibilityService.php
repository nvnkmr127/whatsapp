<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Services\OfferAuditService;
use Carbon\Carbon;

/**
 * OfferEligibilityService
 * ========================
 * Determines whether a team is currently eligible to benefit from the
 * "6 Months Free" launch offer.
 *
 * Six eligibility rules (all must pass):
 *
 *  Rule 1 – offer_enabled global flag must be TRUE.
 *  Rule 2 – Current timestamp must be inside the configured offer window
 *            (if no window is configured the offer is always within window).
 *  Rule 3 – The team has never claimed the offer before
 *            (offer_claimed_at is null on the team).
 *  Rule 4 – The team has NOT been manually excluded by a Super Admin
 *            (offer_excluded = false).
 *  Rule 5 – The team has not previously converted (paid) and then churned
 *            (offer_converted_churned = false).
 *  Rule 6 – The team owner has NEVER claimed an offer on ANY prior team
 *            (users.has_claimed_offer = false).
 *            This survives email changes and team deletions.
 */
class OfferEligibilityService
{
    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Returns TRUE only when every rule passes.
     */
    public function isEligible(Team $team): bool
    {
        return $this->evaluate($team)->eligible;
    }

    /**
     * Returns a structured result with every individual rule outcome.
     * Useful for admin dashboards and debug endpoints.
     *
     * @return object{
     *   eligible: bool,
     *   denial_reason: string|null,
     *   rules: array<string, array{pass: bool, message: string}>
     * }
     */
    public function evaluate(Team $team): object
    {
        $rules = [
            'offer_enabled' => $this->checkOfferEnabled(),
            'within_window' => $this->checkWindow(),
            'never_claimed' => $this->checkNeverClaimed($team),
            'not_excluded' => $this->checkNotExcluded($team),
            'not_converted_churned' => $this->checkNotConvertedChurned($team),
            'owner_never_claimed' => $this->checkOwnerNeverClaimed($team),
        ];

        $denialReason = null;
        $eligible = true;

        foreach ($rules as $rule) {
            if (!$rule->pass) {
                $eligible = false;
                $denialReason = $rule->message;
                break; // First failing rule wins; order matters
            }
        }

        return (object) [
            'eligible' => $eligible,
            'denial_reason' => $denialReason,
            'rules' => array_map(fn($r) => ['pass' => $r->pass, 'message' => $r->message], $rules),
        ];
    }

    /**
     * Mark the offer as claimed for a team AND its owner user.
     * Idempotent – safe to call multiple times.
     *
     * Stamping the user ensures claim survives:
     *   • Email change (UserObserver blocks clearing it)
     *   • Team deletion (TeamObserver propagates on deleting)
     *   • WABA disconnect (TeamObserver blocks clearing offer_claimed_at)
     */
    public function markClaimed(Team $team): void
    {
        $claimedAt = now();

        // ── Team-level claim ──────────────────────────────────────────
        if ($team->offer_claimed_at === null) {
            $team->offer_claimed_at = $claimedAt;
            $team->saveQuietly();
        }

        // ── User-level claim (permanent, non-clearable) ───────────────
        $owner = $team->owner ?? User::find($team->user_id);
        if ($owner && !$owner->has_claimed_offer) {
            $owner->has_claimed_offer = true;
            $owner->offer_claimed_at = $claimedAt;
            $owner->saveQuietly();
        }

        // ── Audit trail ───────────────────────────────────────────────
        if ($owner) {
            OfferAuditService::logTrialAssigned($team, $owner);
        }
    }

    /**
     * Mark a team as having converted (paid) and then churned.
     * Called from BillingService when a subscription is cancelled
     * after at least one successful payment.
     */
    public function markConvertedChurned(Team $team): void
    {
        $team->offer_converted_churned = true;
        $team->saveQuietly();
    }

    /**
     * Manually exclude a team from the offer (Super Admin action).
     */
    public function exclude(Team $team): void
    {
        $team->offer_excluded = true;
        $team->saveQuietly();
    }

    /**
     * Re-include a previously excluded team (Super Admin action).
     * Note: this does NOT re-enable the offer for teams that have already
     * claimed it — `offer_claimed_at` is immutable once set.
     */
    public function include(Team $team): void
    {
        $team->offer_excluded = false;
        $team->saveQuietly();
    }

    // -----------------------------------------------------------------------
    // Individual Rule Checks
    // -----------------------------------------------------------------------

    /**
     * Rule 1 – The global `offer_enabled` setting must be truthy.
     */
    protected function checkOfferEnabled(): object
    {
        $enabled = filter_var(get_setting('offer_enabled', true), FILTER_VALIDATE_BOOLEAN);

        return $this->result(
            $enabled,
            $enabled
            ? 'Offer is globally enabled.'
            : 'Offer is globally disabled by Super Admin (offer_enabled = false).'
        );
    }

    /**
     * Rule 2 – Current UTC time must be inside [offer_start_at, offer_end_at].
     *
     * Settings:
     *   offer_start_at  – ISO-8601 datetime; if missing, no lower bound.
     *   offer_end_at    – ISO-8601 datetime; if missing, no upper bound.
     */
    protected function checkWindow(): object
    {
        $now = Carbon::now('UTC');
        $start = get_setting('offer_start_at');
        $end = get_setting('offer_end_at');

        if (!$start && !$end) {
            return $this->result(true, 'No offer window configured – always within window.');
        }

        if ($start && $now->isBefore(Carbon::parse($start, 'UTC'))) {
            return $this->result(false, "Offer has not started yet. It opens at {$start} UTC.");
        }

        if ($end && $now->isAfter(Carbon::parse($end, 'UTC'))) {
            return $this->result(false, "Offer window has closed. It ended at {$end} UTC.");
        }

        return $this->result(true, 'Current time is inside the offer window.');
    }

    /**
     * Rule 3 – The team must never have claimed the offer before.
     *
     * "Claimed" means `offer_claimed_at` was stamped (by markClaimed()).
     * The TeamObserver makes this field immutable once set.
     */
    protected function checkNeverClaimed(Team $team): object
    {
        if ($team->offer_claimed_at !== null) {
            return $this->result(
                false,
                "This team already claimed the offer on {$team->offer_claimed_at->toDateTimeString()} UTC."
            );
        }

        return $this->result(true, 'Team has never claimed the offer before.');
    }

    /**
     * Rule 4 – Team must not be manually excluded by a Super Admin.
     */
    protected function checkNotExcluded(Team $team): object
    {
        if ($team->offer_excluded) {
            return $this->result(
                false,
                'This team has been manually excluded from the offer by a Super Admin.'
            );
        }

        return $this->result(true, 'Team is not manually excluded.');
    }

    /**
     * Rule 5 – Team must not have previously converted (paid) then churned.
     */
    protected function checkNotConvertedChurned(Team $team): object
    {
        if ($team->offer_converted_churned) {
            return $this->result(
                false,
                'This team previously converted to a paid plan and then churned. The offer cannot be re-applied.'
            );
        }

        return $this->result(true, 'Team has not previously converted and churned.');
    }

    /**
     * Rule 6 – The team owner must never have claimed an offer on any team.
     *
     * This is the "No Trial Re-triggering" guard. The flag on the user model:
     *   • Survives email changes  (UserObserver blocks clearing it)
     *   • Survives team deletion  (TeamObserver propagates claim on delete)
     *   • Survives WABA disconnect (TeamObserver blocks offer_claimed_at reset)
     *   • Is set at markClaimed() time on BOTH the team and the owner user
     */
    protected function checkOwnerNeverClaimed(Team $team): object
    {
        $owner = $team->owner ?? User::find($team->user_id);

        if (!$owner) {
            // No owner found — fail safely
            return $this->result(false, 'Team has no associated owner user.');
        }

        if ($owner->has_claimed_offer) {
            return $this->result(
                false,
                "The account owner ({$owner->email}) has previously claimed the launch offer and is not eligible for another."
            );
        }

        return $this->result(true, 'Account owner has never claimed an offer before.');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function result(bool $pass, string $message): object
    {
        return (object) ['pass' => $pass, 'message' => $message];
    }
}
