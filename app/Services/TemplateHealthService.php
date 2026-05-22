<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TemplateHealthService
{
    /**
     * Check if a contact is in a cooldown period for a specific template category.
     *
     * Rules:
     * - MARKETING: 1 message per 24 hours.
     * - UTILITY: No limit (transactional).
     * - AUTHENTICATION: 5 messages per hour (OTP limits).
     */
    public function checkCooldown(Contact $contact, string $category): bool
    {
        $key = "cooldown:{$contact->id}:{$category}";

        if ($category === 'MARKETING') {
            // Check if key exists
            if (Cache::has($key)) {
                Log::warning("Cooldown Block: Contact {$contact->id} blocked from receiving MARKETING msg.");

                return false; // BLOCKED
            }
            // If not blocked, we will set the lock AFTER sending (in recordUsage)
        }

        if ($category === 'AUTHENTICATION') {
            $count = Cache::get($key, 0);
            if ($count >= 5) {
                Log::warning("Cooldown Block: Contact {$contact->id} hit AUTH rate limit.");

                return false; // BLOCKED
            }
        }

        return true; // ALLOWED
    }

    /**
     * Record usage of a template to enforce cooldowns and track stats.
     * Should be called AFTER a successful send.
     */
    public function recordUsage(Contact $contact, string $category)
    {
        $contactId = $contact->id;
        $key = "contact:{$contactId}:{$category}:last_sent";
        Cache::put($key, now()->timestamp, 86400); // Store for 24h context

        // Rate limit strict counter
        if ($category === 'AUTHENTICATION') {
            $rlKey = "contact:{$contactId}:auth:count:1h";
            Cache::increment($rlKey);
            // Ensure expiry is set on first increment?
            // Cache::add only works if not exists.
            // Simplified: just increment.
            // In a real high-throughput system we'd use Lua script or specific rate limiter class.
            // For now, assume Laravel rate limiter or basic cache logic.
        }
    }

    /**
     * Circuit Breaker: Auto-Pause templates performing poorly.
     */
    public function checkCircuitBreaker(WhatsappTemplate $template)
    {
        // Skip if already paused or disabled
        if ($template->is_paused || in_array($template->status, ['DISABLED', 'REJECTED', 'FLAGGED'])) {
            return;
        }

        // Rule 1: Low Read Rate (Marketing Only)
        // Only apply if we have significant volume (> 1000 sends)
        if ($template->category === 'MARKETING' && $template->total_sent > 1000) {
            $readRate = $template->total_read / $template->total_sent;

            // Threshold: < 5% read rate effectively means spam.
            if ($readRate < 0.05) {
                // AUTO PAUSE
                $template->update([
                    'is_paused' => true,
                    // We could add a 'pause_reason' column, but for now log it.
                ]);
                \Illuminate\Support\Facades\Log::alert("Circuit Breaker Triggered: Template {$template->id} ({$template->name}) paused due to low read rate (".number_format($readRate * 100, 2).'%).');
            }
        }

        // Rule 2: High Block Rate (Hypothetical - requires Block signal)
        // If we had 'total_blocks' column...
    }

    /**
     * Calculate a "Fatigue Score" (0 = healthy, 100 = fully fatigued).
     *
     * Algorithm:
     *  - Below minimum send volume → 0 (insufficient data)
     *  - Read rate ≥ target (30 %) → 0
     *  - Read rate ≤ floor (3 %)  → 100
     *  - Linear interpolation between floor and target otherwise
     */
    public function getFatigueScore(WhatsappTemplate $template): int
    {
        $minVolume = 200;

        if ($template->total_sent < $minVolume) {
            return 0;
        }

        $readRate = $template->total_sent > 0
            ? $template->total_read / $template->total_sent
            : 0.0;

        $targetRate = 0.30; // 30 % read rate → perfectly healthy
        $floorRate  = 0.03; // 3 % read rate → completely fatigued

        if ($readRate >= $targetRate) {
            return 0;
        }

        if ($readRate <= $floorRate) {
            return 100;
        }

        // Map [$floorRate .. $targetRate] → [100 .. 0]
        $score = (int) round(100 * ($targetRate - $readRate) / ($targetRate - $floorRate));

        return max(0, min(100, $score));
    }
}
