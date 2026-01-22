<?php

namespace App\Validators;

use App\DTOs\ValidationError;
use App\DTOs\ValidationResult;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class RateLimitValidator
{
    /**
     * Get tier limits
     */
    protected function getTierLimit(string $tier): int
    {
        return match ($tier) {
            'TIER_1K' => 1000,
            'TIER_10K' => 10000,
            'TIER_100K' => 100000,
            'TIER_UNLIMITED' => PHP_INT_MAX,
            default => 1000,
        };
    }

    /**
     * Get 24-hour message usage
     */
    protected function get24HourUsage(Team $team): int
    {
        return DB::table('messages')
            ->where('team_id', $team->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Get reset time (midnight)
     */
    protected function getResetTime(): string
    {
        return now()->addDay()->startOfDay()->toIso8601String();
    }

    /**
     * Validate rate limits
     */
    public function validate(Team $team, int $messageCount): ValidationResult
    {
        $result = new ValidationResult();

        $tier = $team->wm_messaging_limit ?? 'TIER_1K';
        $dailyLimit = $this->getTierLimit($tier);
        $currentUsage = $this->get24HourUsage($team);
        $remaining = $dailyLimit - $currentUsage;

        // Check if would exceed daily limit
        if ($messageCount > $remaining) {
            $result->addError(new ValidationError(
                code: 'DAILY_LIMIT_EXCEEDED',
                message: "Would exceed daily limit ({$currentUsage}/{$dailyLimit} used, {$remaining} remaining)",
                severity: 'error',
                field: 'message_count',
                suggestion: $remaining > 0
                ? "Reduce to {$remaining} messages or wait for limit reset"
                : "Wait for limit reset or upgrade tier",
                metadata: [
                    'tier' => $tier,
                    'daily_limit' => $dailyLimit,
                    'current_usage' => $currentUsage,
                    'remaining' => $remaining,
                    'requested' => $messageCount,
                    'resets_at' => $this->getResetTime(),
                    'upgrade_available' => $this->canUpgradeTier($tier),
                ]
            ));
        }
        // Warn at 80%
        elseif ((($currentUsage + $messageCount) / $dailyLimit) > 0.8) {
            $usagePercent = round((($currentUsage + $messageCount) / $dailyLimit) * 100, 2);

            $result->addError(new ValidationError(
                code: 'APPROACHING_DAILY_LIMIT',
                message: "Approaching daily limit ({$usagePercent}% of limit will be used)",
                severity: 'warning',
                field: 'message_count',
                suggestion: 'Consider spreading sends throughout the day or upgrading tier',
                metadata: [
                    'usage_percent' => $usagePercent,
                    'current_usage' => $currentUsage,
                    'after_send_usage' => $currentUsage + $messageCount,
                    'daily_limit' => $dailyLimit,
                ]
            ));
        }

        return $result;
    }

    /**
     * Check if tier can be upgraded
     */
    protected function canUpgradeTier(string $currentTier): bool
    {
        return !in_array($currentTier, ['TIER_UNLIMITED']);
    }
}
