<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamWallet;

/**
 * OutboundPreflightService
 * ════════════════════════════════════════════════════════
 * CLEAR BILLING PRECEDENCE LOGIC DEFINED
 * ────────────────────────────────────────────────────────
 * Before ANY outbound action (Message send, API Call, Flow execution),
 * the system MUST validate state in this exact, immutable order:
 *
 * 1. Trial Validity: Is the trial active and unexpired? (Hard lock)
 * 2. Grace State: If paid, is the subscription within the 1-3 day grace period?
 * 3. Plan Entitlement: Does the active plan/trial actually grant this feature?
 * 4. Usage Limit: Has the monthly quota for this specific action been breached?
 * 5. Wallet Balance: Does the team have enough pre-paid wallet balance to cover the action cost?
 * 6. Meta-Level Limits: (If applicable) Is the WABA account healthy and under Meta's daily API limits?
 *
 * Centralizing this here prevents scattered tier-checks and ensures
 * zero retroactive billing or silent failures.
 */
class OutboundPreflightService
{
    public function __construct(
        private readonly EntitlementService $entitlements,
    ) {}

    /**
     * Run the complete preflight checklist.
     * Returns an array: ['allowed' => bool, 'reason' => string, 'code' => string]
     *
     * @param  Team  $team  The executing team
     * @param  string  $feature  The entitlement feature key (e.g., 'send_message', 'calling')
     * @param  string|null  $limitKey  The usage limit key (e.g., 'message_limit', 'call_minutes')
     * @param  int|float  $currentUsage  Current usage count to evaluate against the limit
     * @param  float  $cost  Cost of the action to check against the wallet
     */
    /**
     * Same as authorize(), but throws instead of returning a denial array.
     */
    public function authorizeOrFail(
        Team $team,
        string $feature,
        ?string $limitKey = null,
        float $currentUsage = 0,
        float $cost = 0.00
    ): void {
        $preflight = $this->authorize($team, $feature, $limitKey, $currentUsage, $cost);
        if (! $preflight['allowed']) {
            throw new \Exception("[{$preflight['code']}] {$preflight['reason']}");
        }
    }

    public function authorize(
        Team $team,
        string $feature,
        ?string $limitKey = null,
        float $currentUsage = 0,
        float $cost = 0.00
    ): array {
        $e = $this->entitlements->for($team);

        // ── 1. & 2. Overarching Active State (Trial & Grace) ────────
        // $e->active() encompasses Trial checks, Paid validity, and Grace periods.
        // It returns false if Trial is expired OR if Paid is past Grace.
        if (! $e->active()) {
            return [
                'allowed' => false,
                'code' => 'ERR_SUBSCRIPTION_INACTIVE',
                'reason' => $e->statusLabel().' — Subscription or trial has expired.',
            ];
        }

        // ── 3. Plan Entitlement ─────────────────────────────────────
        // Explicitly check if the current tier (Trial or Paid) enables this capability.
        if (! $e->can($feature)) {
            return [
                'allowed' => false,
                'code' => 'ERR_FEATURE_LOCKED',
                'reason' => "Plan does not support '{$feature}'.",
            ];
        }

        // ── 4. Usage Limit ──────────────────────────────────────────
        // If they have exceeded their quota, they can still proceed IF they are paying for it out of pocket.
        // However, if it's a free action (cost <= 0) and they exceed quota, block it.
        $isOverQuota = false;
        $limit = null;

        if ($limitKey) {
            $limit = $e->limit($limitKey);
            if ($limit !== null && $limit >= 0 && $currentUsage >= $limit) {
                $isOverQuota = true;
            }
        }

        if ($isOverQuota && $cost <= 0) {
            return [
                'allowed' => false,
                'code' => 'ERR_QUOTA_EXCEEDED',
                'reason' => "Monthly quota of {$limit} for {$limitKey} has been reached. Please top-up your wallet or upgrade to continue.",
            ];
        }

        // ── 5. Wallet Balance ───────────────────────────────────────
        if ($cost > 0) {
            $wallet = TeamWallet::where('team_id', $team->id)->first();
            $balance = $wallet ? (float) $wallet->balance : 0.00;

            if ($balance < $cost) {
                if ($isOverQuota) {
                    return [
                        'allowed' => false,
                        'code' => 'ERR_QUOTA_EXCEEDED',
                        'reason' => "Monthly quota of {$limit} for {$limitKey} has been reached. Please top-up your wallet to continue.",
                    ];
                }

                return [
                    'allowed' => false,
                    'code' => 'ERR_INSUFFICIENT_FUNDS',
                    'reason' => 'Wallet balance ($'.number_format($balance, 2).') is insufficient for action cost ($'.number_format($cost, 2).').',
                ];
            }
        }

        // ── 6. Meta-Level Limits (Abstracted Hook) ──────────────────
        // (Typically validated asynchronously via WhatsApp Cloud API Webhooks,
        // but exposed here if local DB tracks Meta Tier (e.g., Tier 1: 1k/day))
        if ($this->isMetaRateLimited($team)) {
            return [
                'allowed' => false,
                'code' => 'ERR_META_RATE_LIMIT',
                'reason' => 'Meta WABA daily sending limit reached or account flagged.',
            ];
        }

        return [
            'allowed' => true,
            'code' => 'OK',
            'reason' => 'All preflight checks passed.',
        ];
    }

    /**
     * Check if local Meta sync indicates a block.
     */
    private function isMetaRateLimited(Team $team): bool
    {
        // For example, if the WABA is restricted or quality is RED.
        return $team->isWhatsAppSuspended();
    }
}
