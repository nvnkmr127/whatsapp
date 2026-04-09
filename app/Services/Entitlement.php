<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Entitlement
 * ═══════════
 * Immutable value object returned by EntitlementService::for($team).
 *
 * Provides a clean, discoverable API for all subscription / feature / limit
 * decisions. No module ever inspects raw Team columns directly — it calls
 * methods on this object.
 *
 * @readonly
 */
final class Entitlement
{
    public function __construct(
        public readonly int $teamId,
        public readonly string $planName,
        public readonly string $status,           // raw DB column value
        public readonly bool $active,           // can use the product right now
        public readonly bool $onTrial,          // trial is active (not expired)
        public readonly bool $inGrace,          // paid plan, within grace window
        public readonly bool $expired,          // access permanently lost
        public readonly bool $offerEligible,    // qualifies for launch offer limits/features
        public readonly int $trialDaysRemaining,
        public readonly ?Carbon $trialEndsAt,
        /** @var array<string, bool> */
        public readonly array $features,
        /** @var array<string, int> */
        public readonly array $limits,
        /** @var array<string, int> */
        public readonly array $usage,
    ) {}

    // ------------------------------------------------------------------
    // Status helpers
    // ------------------------------------------------------------------

    /** The team has any form of active access (trial, paid, grace). */
    public function active(): bool
    {
        return $this->active;
    }

    /** Currently in a trial period (not expired). */
    public function onTrial(): bool
    {
        return $this->onTrial;
    }

    /** Paid subscription past end_at but within the grace window. */
    public function inGrace(): bool
    {
        return $this->inGrace;
    }

    /** No valid access — trial over, paid expired, or manually expired. */
    public function expired(): bool
    {
        return $this->expired;
    }

    /** Team qualifies for the 6-month launch offer limits & features. */
    public function offerEligible(): bool
    {
        return $this->offerEligible;
    }

    /** Remaining trial days (0 when not on trial or trial just ended). */
    public function trialDaysRemaining(): int
    {
        return $this->trialDaysRemaining;
    }

    /** Human-readable subscription status with context. */
    public function statusLabel(): string
    {
        if ($this->expired) {
            return 'Expired';
        }
        if ($this->onTrial) {
            if ($this->trialEndsAt === null) {
                return 'Trial';
            }
            $days = $this->trialDaysRemaining;

            return $days > 0 ? "Trial ({$days}d left)" : 'Trial (expiring today)';
        }
        if ($this->inGrace) {
            return 'Grace Period';
        }

        return match ($this->status) {
            'active' => 'Active',
            'canceled' => 'Canceled',
            default => ucfirst($this->status),
        };
    }

    /** Current subscription plan identifier. */
    public function planName(): string
    {
        return $this->planName;
    }

    // ------------------------------------------------------------------
    // Feature flag API
    // ------------------------------------------------------------------

    /**
     * Returns TRUE if the feature is available to this team.
     * Checks: active status, offer features, plan features, add-ons, overrides.
     *
     * Do NOT check $team->hasFeature() in modules — use this instead.
     */
    public function hasFeature(string $feature): bool
    {
        if ($this->bypassAllAccess()) {
            return true;
        }

        if (! $this->active) {
            return false;
        }

        return $this->features[$feature] ?? false;
    }

    // ------------------------------------------------------------------
    // Limit API
    // ------------------------------------------------------------------

    /**
     * Returns the effective limit for the given key.
     * 0 = unlimited.
     *
     * Do NOT call $team->getPlanLimit() in modules — use this instead.
     */
    public function limit(string $key): int
    {
        if ($this->bypassAllAccess()) {
            return 0; // unlimited
        }

        return $this->limits[$key] ?? 0;
    }

    /**
     * Whether the team is within the given limit for the given current usage.
     * Pass the usage yourself for custom resource types, or rely on the
     * pre-resolved usage snapshot for messages/agents.
     */
    public function withinLimit(string $key, ?int $currentUsage = null): bool
    {
        if ($this->bypassAllAccess()) {
            return true;
        }

        $max = $this->limit($key);
        if ($max === 0) {
            return true; // Unlimited
        }
        $used = $currentUsage ?? ($this->usage[$key.'_count'] ?? $this->usage[$key.'_this_month'] ?? 0);

        return $used < $max;
    }

    // ------------------------------------------------------------------
    // Capability API (combined feature + limit check)
    // ------------------------------------------------------------------

    /**
     * Unified capability check — mirrors Team::canAccess() but routes through
     * this value object so all decisions are traceable and testable.
     *
     * Feature slugs:  'chat', 'contacts', 'templates', 'campaigns',
     *                 'automations', 'analytics', 'commerce', 'ai',
     *                 'api_access', 'webhooks', 'flows', 'backups', ...
     *
     * Resource slugs: 'send_message', 'add_agent'
     */
    public function can(string $capability): bool
    {
        if ($this->bypassAllAccess()) {
            return true;
        }

        if (! $this->active) {
            return false;
        }

        return match ($capability) {
            'send_message' => $this->canSendMessage(),
            'add_agent' => $this->canAddAgent(),
            default => $this->hasFeature($capability),
        };
    }

    /**
     * Inverse of can() — returns a human-readable denial reason when the
     * capability is blocked, or null when it is allowed.
     */
    public function denialReason(string $capability): ?string
    {
        if ($this->bypassAllAccess()) {
            return null;
        }

        if ($this->expired) {
            return "Your {$this->statusLabel()} subscription does not permit access to '{$capability}'.";
        }

        if (! $this->active) {
            return 'Your account is inactive. Please contact billing.';
        }

        if (! $this->can($capability)) {
            return "Your current plan does not include '{$capability}'.";
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Serialisation (APIs / debug / admin dashboard)
    // ------------------------------------------------------------------

    /**
     * Full snapshot suitable for API responses and admin debug endpoints.
     */
    public function toArray(): array
    {
        return [
            'team_id' => $this->teamId,
            'plan' => $this->planName,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'active' => $this->active,
            'on_trial' => $this->onTrial,
            'in_grace' => $this->inGrace,
            'expired' => $this->expired,
            'offer_eligible' => $this->offerEligible,
            'trial_days_remaining' => $this->trialDaysRemaining,
            'trial_ends_at' => $this->trialEndsAt?->toDateTimeString(),
            'features' => $this->features,
            'limits' => $this->limits,
            'usage' => $this->usage,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    // ------------------------------------------------------------------
    // Internal capability checks
    // ------------------------------------------------------------------

    private function canSendMessage(): bool
    {
        $limit = $this->limit('message_limit');
        if ($limit === 0) {
            return true;
        }

        return ($this->usage['messages_this_month'] ?? 0) < $limit;
    }

    private function canAddAgent(): bool
    {
        $limit = $this->limit('agent_limit');
        if ($limit === 0) {
            return true;
        }

        return ($this->usage['agent_count'] ?? 0) < $limit;
    }

    private function bypassAllAccess(): bool
    {
        return (bool) config('app.full_access_all', false);
    }
}
