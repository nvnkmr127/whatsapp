<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CallSafeguardService
{
    /**
     * Evaluate if a call can be initiated based on rate limits and suspension.
     */
    public function evaluateOutboundEligibility(Team $team): array
    {
        // 1. Check Suspension
        if ($team->calling_suspended_until && $team->calling_suspended_until->isFuture()) {
            return [
                'allowed' => false,
                'reason' => 'TEAM_CALLING_SUSPENDED',
                'retry_after' => $team->calling_suspended_until,
            ];
        }

        // 2. Check Rate Limits (Minutely)
        $minLimit = $this->getConfig($team, 'rate_limit_minute', 5);
        $minKey = "call_rate_min_{$team->id}_" . now()->format('YmdHi');
        $minCount = Cache::get($minKey, 0);

        if ($minCount >= $minLimit) {
            return [
                'allowed' => false,
                'reason' => 'RATE_LIMIT_MINUTE_EXCEEDED',
                'retry_after' => now()->addMinute()->startOfMinute(),
            ];
        }

        // 3. Check Rate Limits (Hourly)
        $hourLimit = $this->getConfig($team, 'rate_limit_hour', 60);
        $hourKey = "call_rate_hour_{$team->id}_" . now()->format('YmdH');
        $hourCount = Cache::get($hourKey, 0);

        if ($hourCount >= $hourLimit) {
            return [
                'allowed' => false,
                'reason' => 'RATE_LIMIT_HOUR_EXCEEDED',
                'retry_after' => now()->addHour()->startOfHour(),
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Increment rate limit counters.
     */
    public function recordOutboundCall(Team $team): void
    {
        $minKey = "call_rate_min_{$team->id}_" . now()->format('YmdHi');
        $hourKey = "call_rate_hour_{$team->id}_" . now()->format('YmdH');

        Cache::put($minKey, Cache::get($minKey, 0) + 1, 120);
        Cache::put($hourKey, Cache::get($hourKey, 0) + 1, 3600);
    }

    /**
     * Record a terminal event (missed/failed) to evaluate thresholds.
     */
    public function recordEvent(Team $team, string $type): void
    {
        if (!in_array($type, ['missed', 'failed'])) {
            return;
        }

        $window = $this->getConfig($team, 'missed_call_window', 30); // minutes
        $threshold = $this->getConfig($team, 'missed_call_threshold', 5);

        $key = "missed_calls_{$team->id}";
        $events = Cache::get($key, []);

        // Add new event
        $events[] = now()->timestamp;

        // Filter events within window
        $cutoff = now()->subMinutes($window)->timestamp;
        $events = array_filter($events, fn($t) => $t > $cutoff);

        Cache::put($key, $events, $window * 60);

        if (count($events) >= $threshold) {
            $this->suspendCalling($team, 'Threshold for missed calls reached.');
        }
    }

    /**
     * Suspend calling for a team.
     */
    protected function suspendCalling(Team $team, string $reason): void
    {
        $period = $this->getConfig($team, 'auto_suspension_period', 15); // minutes
        $until = now()->addMinutes($period);

        $team->update(['calling_suspended_until' => $until]);

        Log::warning("Calling suspended for team {$team->id}", [
            'reason' => $reason,
            'until' => $until->toDateTimeString(),
        ]);

        // Emit notification / alert here if needed
    }

    /**
     * Get a safeguard config value or default.
     */
    protected function getConfig(Team $team, string $key, $default)
    {
        return $team->calling_safeguards[$key] ?? $default;
    }
}
