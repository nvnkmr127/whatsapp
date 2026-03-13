<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Team;
use App\Services\OfferEligibilityService;
use App\Services\OfferSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * EntitlementService
 * ══════════════════
 * The SINGLE source of truth for all subscription, trial, grace-period,
 * feature-flag, and usage-limit decisions.
 *
 * No other module should independently check subscription_status, trial_ends_at,
 * or call getPlanLimit() / hasFeature() with its own logic. Instead every
 * module resolves an Entitlement value-object from this service:
 *
 *   $e = app(EntitlementService::class)->for($team);
 *
 *   $e->active()                 // team can use the product at all
 *   $e->onTrial()                // currently in trial period
 *   $e->inGrace()                // paid plan, past end_at but within grace window
 *   $e->expired()                // access permanently lost
 *   $e->offerEligible()          // qualifies for 6-month launch offer
 *   $e->trialDaysRemaining()     // int
 *   $e->can('send_message')      // capability check (features + limits combined)
 *   $e->hasFeature('ai')         // feature flag
 *   $e->limit('message_limit')   // int – 0 = unlimited
 *   $e->planName()               // string
 *   $e->statusLabel()            // 'Trial (4d left)' / 'Active' / 'Expired' / ...
 *   $e->toArray()                // full snapshot for APIs / debug endpoints
 *
 * Results are cached per-team for the duration of a single HTTP request
 * so repeated calls inside the same request are free.
 *
 * ────────────────────────────────────────────────────────────────────
 * Migration guide for existing modules:
 *
 *   Before:
 *     if ($team->subscription_status === 'trial' && $team->trial_ends_at->isPast())
 *
 *   After:
 *     $e = app(EntitlementService::class)->for($team);
 *     if ($e->expired())
 *
 *   Before:
 *     $team->getPlanLimit('message_limit', 1000)
 *
 *   After:
 *     app(EntitlementService::class)->for($team)->limit('message_limit')
 *
 *   Before:
 *     $team->hasFeature('ai')
 *
 *   After:
 *     app(EntitlementService::class)->for($team)->hasFeature('ai')
 *
 *   Before:
 *     $team->canAccess('send_message')
 *
 *   After:
 *     app(EntitlementService::class)->for($team)->can('send_message')
 * ────────────────────────────────────────────────────────────────────
 */
class EntitlementService
{
    /** In-request cache of resolved Entitlement objects, keyed by team ID. */
    private array $cache = [];

    public function __construct(
        private readonly OfferEligibilityService $offerEligibility,
        private readonly OfferSettingsService $offerSettings,
    ) {
    }

    // ------------------------------------------------------------------
    // Primary API
    // ------------------------------------------------------------------

    /**
     * Resolve (and cache) an Entitlement for the given team.
     * Subsequent calls for the same team within the same request are O(1).
     */
    public function for(Team $team): Entitlement
    {
        if (isset($this->cache[$team->id])) {
            return $this->cache[$team->id];
        }

        return $this->cache[$team->id] = $this->resolve($team);
    }

    /**
     * Flush the in-request cache for a specific team (or all teams).
     * Call after any subscription change to force re-evaluation.
     */
    public function flush(?Team $team = null): void
    {
        if ($team) {
            unset($this->cache[$team->id]);
        } else {
            $this->cache = [];
        }
    }

    // ------------------------------------------------------------------
    // Resolution
    // ------------------------------------------------------------------

    private function resolve(Team $team): Entitlement
    {
        $status = $team->subscription_status ?? 'trial';
        $now = Carbon::now();

        // ── 1. Subscription status classification ──────────────────────
        $onTrial = ($status === 'trial');
        // If trial_ends_at is null, we assume it is still active (unlimited trial)
        $trialActive = $onTrial && ($team->trial_ends_at === null || $team->trial_ends_at->isFuture());
        $trialExpired = $onTrial && $team->trial_ends_at && $team->trial_ends_at->isPast();

        $isPaid = in_array($status, ['active', 'canceled'], true);
        $paidExpired = $isPaid && $team->subscription_ends_at && $team->subscription_ends_at->isPast();

        $inGrace = $paidExpired && $this->isInGracePeriod($team, $now);
        $hardExpired = ($status === 'expired') || $trialExpired || ($paidExpired && !$inGrace);

        // Active = can use the product right now
        $active = !$hardExpired;

        // ── 2. Offer eligibility / Aktivität ─────────────────────────────
        // A team qualifies for offer benefits if they are on an active trial AND
        // (have already claimed it OR currently qualify to claim it).
        $offerEligible = $onTrial && $active && (
            $team->offer_claimed_at !== null || 
            $this->offerEligibility->isEligible($team)
        );

        // ── 3. Feature flags ───────────────────────────────────────────
        // Resolved via the same chain as Team::hasFeature() but all in one place
        $features = $this->resolveFeatures($team, $active, $offerEligible);

        // ── 4. Quantity limits ─────────────────────────────────────────
        $limits = $this->resolveLimits($team, $active, $offerEligible);

        // ── 5. Usage snapshot ──────────────────────────────────────────
        $usage = $this->resolveUsage($team);

        // ── 6. Trial countdown ────────────────────────────────────────
        $trialDaysRemaining = ($trialActive && $team->trial_ends_at)
            ? max(0, (int) $now->diffInDays($team->trial_ends_at, false))
            : 0;

        return new Entitlement(
            teamId: $team->id,
            planName: $team->subscription_plan ?? 'trial',
            status: $status,
            active: $active,
            onTrial: $trialActive,
            inGrace: $inGrace,
            expired: $hardExpired,
            offerEligible: $offerEligible,
            trialDaysRemaining: $trialDaysRemaining,
            trialEndsAt: $team->trial_ends_at,
            features: $features,
            limits: $limits,
            usage: $usage,
        );
    }

    // ------------------------------------------------------------------
    // Feature resolution
    // ------------------------------------------------------------------

    /**
     * Returns a map of feature_slug → bool.
     * All features come from this one place — no module decides independently.
     */
    private function resolveFeatures(Team $team, bool $active, bool $offerEligible): array
    {
        if (!$active) {
            // Hard-expired → all features off
            return $this->allFeaturesOff($team);
        }

        // Collect override-enabled features
        $overrideFeatures = $team->billingOverrides()
            ->where('type', 'feature_enable')
            ->active()
            ->pluck('key')
            ->flip()
            ->map(fn() => true)
            ->toArray();

        // Offer features
        $offerFeatures = [];
        if ($offerEligible) {
            $offered = $this->offerSettings->includedFeatures();
            $offerFeatures = array_fill_keys($offered, true);
        }

        // Plan features
        $plan = $this->resolvePlan($team);
        $planFeatures = [];
        if ($plan) {
            $planFeatureKeys = [
                'chat',
                'contacts',
                'templates',
                'campaigns',
                'automations',
                'analytics',
                'commerce',
                'ai',
                'api_access',
                'webhooks',
                'flows',
                'backups',
                'cloud_backups',
            ];
            foreach ($planFeatureKeys as $f) {
                if ($plan->hasFeature($f)) {
                    $planFeatures[$f] = true;
                }
            }
        }

        // Add-on features
        $addonFeatures = $team->addOns()
            ->get()
            ->filter(fn($a) => $a->isActive())
            ->pluck('type')
            ->flip()
            ->map(fn() => true)
            ->toArray();

        // Merge: overrides > offer > plan > add-on
        return array_merge($planFeatures, $addonFeatures, $offerFeatures, $overrideFeatures);
    }

    private function allFeaturesOff(Team $team): array
    {
        // Keep override features even when expired (admin granted specific access)
        return $team->billingOverrides()
            ->where('type', 'feature_enable')
            ->active()
            ->pluck('key')
            ->flip()
            ->map(fn() => true)
            ->toArray();
    }

    // ------------------------------------------------------------------
    // Limit resolution
    // ------------------------------------------------------------------

    /**
     * Returns a map of limit_key → int (0 = unlimited).
     * All limits resolved from a single, canonical path.
     */
    private function resolveLimits(Team $team, bool $active, bool $offerEligible): array
    {
        // All known limit keys (union of LIMIT_MAP keys + known plan keys)
        $limitKeys = array_merge(
            array_keys($this->offerSettings->limitMap()),
            [
                'ai_conversation_limit',
                'automation_run_limit',
                'contact_limit',
                'max_backups_per_team',
                'max_storage_mb',
                'cooldown_hours_between_backups'
            ]
        );

        $limits = [];
        foreach ($limitKeys as $key) {
            $limits[$key] = $this->resolveOneLimit($team, $key, $active, $offerEligible);
        }

        return $limits;
    }

    private function resolveOneLimit(Team $team, string $key, bool $active, bool $offerEligible): int
    {
        // 1. Active billing override
        $override = $team->billingOverrides()
            ->where('type', 'limit_increase')
            ->where('key', $key)
            ->active()
            ->latest()
            ->first();

        if ($override) {
            return (int) $override->value;
        }

        // 2. Offer limit (if eligible)
        // If a snapshot exists, use it (Static Allocation). Otherwise use global settings (Dynamic).
        if ($offerEligible && $this->offerSettings->hasLimitMapping($key)) {
            $snapshot = $team->offer_snapshot;
            $limitKey = $this->offerSettings->limitKey($key); // e.g., 'offer_message_limit'

            // Prioritize snapshot values if available
            if (is_array($snapshot) && array_key_exists($limitKey, $snapshot)) {
                return (int) $snapshot[$limitKey];
            }
            
            // Fallback to dynamic global settings if no snapshot exists
            return $this->offerSettings->limitValue($key) ?? 0;
        }

        // 3. Plan limit
        $plan = $this->resolvePlan($team);
        if ($plan) {
            return (int) ($plan->{$key} ?? 0);
        }

        return 0;
    }

    // ------------------------------------------------------------------
    // Usage snapshot
    // ------------------------------------------------------------------

    /**
     * Current-month usage metrics (cached inside this service call to avoid
     * duplicate DB queries when multiple limits are checked).
     */
    private function resolveUsage(Team $team): array
    {
        $month = now()->month;
        $year = now()->year;

        $messages = Message::where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $agents = $team->users()->count() + $team->teamInvitations()->count();

        // Backup usage
        $backupStats = \App\Models\TenantBackup::where('team_id', $team->id)
            ->where('status', 'completed')
            ->selectRaw('count(*) as count, sum(size) as total_size')
            ->first();

        $lastBackup = \App\Models\TenantBackup::where('team_id', $team->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        return [
            'messages_this_month' => $messages,
            'agent_count' => $agents,
            'max_backups_per_team_count' => $backupStats->count ?? 0,
            'max_storage_mb_count' => round(($backupStats->total_size ?? 0) / (1024 * 1024), 2),
            'last_backup_at' => $lastBackup?->created_at,
        ];
    }

    // ------------------------------------------------------------------
    // Grace period
    // ------------------------------------------------------------------

    /**
     * A team is in grace when subscription_ends_at is past but
     * subscription_grace_ends_at is still in the future.
     */
    private function isInGracePeriod(Team $team, Carbon $now): bool
    {
        return $team->subscription_grace_ends_at !== null &&
            $team->subscription_grace_ends_at->isFuture();
    }

    // ------------------------------------------------------------------
    // Plan resolution (memoised)
    // ------------------------------------------------------------------

    private array $planCache = [];

    private function resolvePlan(Team $team): ?object
    {
        $name = $team->subscription_plan ?? 'basic';

        if (!isset($this->planCache[$name])) {
            $this->planCache[$name] = \App\Models\Plan::where('name', $name)->first();
        }

        return $this->planCache[$name];
    }
}
