<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\OfferAuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * TrialOverrideService
 * ════════════════════
 * The ONLY place Super Admins programmatically change trial state.
 * Every action is:
 *   - Validated before execution
 *   - Wrapped in a DB transaction
 *   - Followed by an EntitlementService cache flush
 *   - Fully audit-logged via OfferAuditService
 *
 * Four actions:
 *   forceTrial()    — assign trial to any team, bypassing eligibility rules
 *   extendTrial()   — add N days to an existing trial
 *   revokeTrial()   — immediately expire the trial
 *   convertToActive() — promote to active (paid) subscription
 *
 * All methods return a structured result:
 *   ['success' => bool, 'message' => string, 'team' => Team]
 */
class TrialOverrideService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
    ) {
    }

    // ------------------------------------------------------------------
    // 1. Force Trial (bypass eligibility)
    // ------------------------------------------------------------------

    /**
     * Force-assign a trial to any team, regardless of eligibility rules.
     * Use when you want to grant a trial to a team that failed eligibility
     * (e.g., previously claimed, different domain, etc.) as a manual exception.
     *
     * @param  int|null  $months  Null = use offer_trial_months setting
     * @param  string    $reason  Mandatory reason for audit log
     */
    public function forceTrial(Team $team, User $admin, ?int $months = null, string $reason = ''): array
    {
        $months = $months ?? (int) get_setting('offer_trial_months', 6);
        $endsAt = now()->addMonths($months);

        DB::transaction(function () use ($team, $endsAt) {
            $team->update([
                'subscription_status' => 'trial',
                'trial_ends_at' => $endsAt,
                'subscription_ends_at' => null,
            ]);
        });

        $this->entitlements->flush($team);

        OfferAuditService::logTrialForced($team, $admin, $months, $endsAt, $reason);

        return [
            'success' => true,
            'message' => "Trial forced for {$team->name} — expires {$endsAt->toDateString()} ({$months} months). Reason: {$reason}",
            'team' => $team->fresh(),
        ];
    }

    // ------------------------------------------------------------------
    // 2. Extend Trial
    // ------------------------------------------------------------------

    /**
     * Extend an existing trial by N days.
     * Works regardless of current status — if the team is expired, this
     * resurrects the trial from today + $days.
     *
     * @param  int  $days  Number of days to add
     */
    public function extendTrial(Team $team, User $admin, int $days, string $reason = ''): array
    {
        if ($days < 1 || $days > 730) {
            return ['success' => false, 'message' => 'Extension must be between 1 and 730 days.', 'team' => $team];
        }

        $currentEndsAt = $team->trial_ends_at && $team->trial_ends_at->isFuture()
            ? $team->trial_ends_at
            : now();                              // If expired, extend from today

        $newEndsAt = $currentEndsAt->addDays($days);

        DB::transaction(function () use ($team, $newEndsAt) {
            $team->update([
                'subscription_status' => 'trial',
                'trial_ends_at' => $newEndsAt,
            ]);
        });

        $this->entitlements->flush($team);

        OfferAuditService::logTrialExtended($team, $admin, $days, $newEndsAt, $reason);

        return [
            'success' => true,
            'message' => "Trial extended by {$days} day(s) for {$team->name}. New expiry: {$newEndsAt->toDateString()}.",
            'team' => $team->fresh(),
        ];
    }

    // ------------------------------------------------------------------
    // 3. Revoke Trial
    // ------------------------------------------------------------------

    /**
     * Immediately revoke a team's trial — sets trial_ends_at to now()
     * so EntitlementService classifies them as expired on the next request.
     */
    public function revokeTrial(Team $team, User $admin, string $reason = ''): array
    {
        if (!in_array($team->subscription_status, ['trial', 'active'], true)) {
            return [
                'success' => false,
                'message' => "Cannot revoke: {$team->name} is already '{$team->subscription_status}'.",
                'team' => $team,
            ];
        }

        DB::transaction(function () use ($team) {
            $team->update([
                'subscription_status' => 'expired',
                'trial_ends_at' => now()->subSecond(), // Ensures expired() === true immediately
                'subscription_ends_at' => now()->subSecond(),
            ]);
        });

        $this->entitlements->flush($team);

        OfferAuditService::logTrialRevoked($team, $admin, $reason);

        return [
            'success' => true,
            'message' => "Trial revoked for {$team->name}. Team status is now 'expired'. Reason: {$reason}",
            'team' => $team->fresh(),
        ];
    }

    // ------------------------------------------------------------------
    // 4. Convert to Active (manual paid conversion)
    // ------------------------------------------------------------------

    /**
     * Promote a team to 'active' subscription status manually.
     * Clears trial dates so EntitlementService treats this as a paid account.
     *
     * @param  string|null  $plan          Subscription plan name (e.g. 'starter', 'growth')
     * @param  int|null     $durationDays  Active subscription duration (null = permanent)
     */
    public function convertToActive(
        Team $team,
        User $admin,
        ?string $plan = null,
        ?int $durationDays = null,
        string $reason = ''
    ): array {
        $plan = $plan ?: ($team->subscription_plan ?? 'starter');
        $endsAt = $durationDays ? now()->addDays($durationDays) : null;

        DB::transaction(function () use ($team, $plan, $endsAt) {
            $team->update([
                'subscription_status' => 'active',
                'subscription_plan' => $plan,
                'trial_ends_at' => null,
                'subscription_ends_at' => $endsAt,
            ]);
        });

        $this->entitlements->flush($team);

        OfferAuditService::logAdminTrialPromo($team, $admin);

        // Additional detail log for plan/duration
        OfferAuditService::logManualConversion($team, $admin, $plan, $endsAt, $reason);

        $expiryMsg = $endsAt ? "expires {$endsAt->toDateString()}" : 'no expiry set';

        return [
            'success' => true,
            'message' => "{$team->name} converted to active on plan '{$plan}' ({$expiryMsg}). Reason: {$reason}",
            'team' => $team->fresh(),
        ];
    }
}
