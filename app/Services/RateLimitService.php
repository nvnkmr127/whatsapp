<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    /**
     * WABA API Tiers and their estimated safe RPS limit.
     */
    protected $tiers = [
        '1K' => 0.04,  // ~1000 per day / 86400s
        '10K' => 0.4,  // ~10000 per day
        '100K' => 4,   // ~100000 per day
        'UNLIMITED' => 50
    ];

    /**
     * Check if a specific team/phone number can send a message.
     */
    public function canSend(int $teamId, string $phoneNumber): bool
    {
        $limit = $this->getEffectiveRps($teamId, $phoneNumber);
        $key = "ratelimit:rps:{$phoneNumber}";

        // Use Cache atomic increment for rate limiting
        $current = Cache::increment($key, 1);
        if ($current === 1) {
            Cache::put($key, 1, 1); // 1 second TTL
        }

        // If we exceed our RPS limit, return false
        if ($current > ceil($limit)) {
            return false;
        }

        return true;
    }

    /**
     * Report a 429 error to trigger adaptive throttling.
     */
    public function reportFailure(int $teamId, string $phoneNumber): void
    {
        $key = "ratelimit:backoff:{$phoneNumber}";

        // Increment backoff intensity (persists for 10 minutes)
        Cache::increment($key, 1);
        Cache::put($key, Cache::get($key, 0), 600);

        Log::warning("RateLimit: Backoff triggered for {$phoneNumber} due to 429/Throttle.");
    }

    /**
     * Get the current effective RPS for a phone number.
     */
    public function getEffectiveRps(int $teamId, string $phoneNumber): float
    {
        $tier = $this->getWabaTier($phoneNumber);
        $baseRps = $this->tiers[$tier] ?? 0.04;

        // Apply adaptive backoff if we've seen failures recently
        $backoffMultiplier = $this->getBackoffMultiplier($phoneNumber);

        return $baseRps * $backoffMultiplier;
    }

    /**
     * Resolve the WABA Tier for a phone number.
     * In a real system, this might involve checking a database field updated by a periodic poll.
     */
    protected function getWabaTier(string $phoneNumber): string
    {
        return Cache::get("waba_tier:{$phoneNumber}", '1K');
    }

    /**
     * Calculate backoff multiplier based on recent failure reports.
     */
    protected function getBackoffMultiplier(string $phoneNumber): float
    {
        $failures = (int) Cache::get("ratelimit:backoff:{$phoneNumber}", 0);

        if ($failures === 0)
            return 1.0;

        // Reduce RPS by 20% for each failure, capped at 90% reduction
        $reduction = min(0.9, $failures * 0.2);

        return 1.0 - $reduction;
    }

    /**
     * Set the WABA Tier for a phone number (manually or via API poll sync).
     */
    public function setWabaTier(string $phoneNumber, string $tier): void
    {
        if (!isset($this->tiers[$tier]))
            return;
        Cache::forever("waba_tier:{$phoneNumber}", $tier);
    }

    /**
     * Pause the entire broadcast system globally.
     */
    public function pauseSystem(): void
    {
        Cache::forever('broadcast_system_paused', true);
        Log::alert("RateLimit: BROADCAST SYSTEM PAUSED GLOBALLY.");
    }

    /**
     * Resume the broadcast system.
     */
    public function resumeSystem(): void
    {
        Cache::forget('broadcast_system_paused');
        Log::info("RateLimit: Broadcast system resumed.");
    }

    /**
     * Check if the system or a specific tenant/number is paused.
     */
    public function isPaused(int $teamId = 0, string $phoneNumber = ''): bool
    {
        // 1. Global system pause
        if (Cache::get('broadcast_system_paused'))
            return true;

        // 2. Tenant level block
        if ($teamId > 0 && Cache::get("tenant_paused:{$teamId}"))
            return true;

        // 3. Selective phone number block (optional)
        if ($phoneNumber && Cache::get("number_paused:{$phoneNumber}"))
            return true;

        return false;
    }

    /**
     * Pause a specific tenant.
     */
    public function pauseTenant(int $teamId, int $duration = 3600): void
    {
        Cache::put("tenant_paused:{$teamId}", true, $duration);
        Log::warning("RateLimit: Tenant {$teamId} paused for {$duration}s.");
    }

    /**
     * Report a critical failure that should trigger an auto-pause for a tenant.
     */
    public function reportCriticalFailure(int $teamId, string $phoneNumber): void
    {
        $this->reportFailure($teamId, $phoneNumber);

        $key = "ratelimit:critical_failures:{$teamId}";
        $count = Cache::increment($key, 1);
        if ($count === 1)
            Cache::put($key, 1, 60);

        // Circuit Breaker: If 10 critical failures occur in 60s, pause the tenant
        if ($count >= 10) {
            $this->pauseTenant($teamId, 300); // 5 min auto-pause
            Cache::forget($key);
            Log::error("RateLimit: Auto-pause (Circuit Breaker) triggered for tenant {$teamId} due to high failure rate.");
        }
    }
}
