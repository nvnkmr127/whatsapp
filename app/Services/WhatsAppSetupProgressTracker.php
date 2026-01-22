<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Cache;

class WhatsAppSetupProgressTracker
{
    /**
     * Save setup progress for resume capability
     */
    public function saveProgress(Team $team, string $step, array $data): void
    {
        $progress = $team->whatsapp_setup_progress ?? [];

        $progress[$step] = [
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
            'attempt' => ($progress[$step]['attempt'] ?? 0) + 1,
        ];

        $team->update(['whatsapp_setup_progress' => $progress]);
    }

    /**
     * Load saved progress for a specific step
     */
    public function loadProgress(Team $team, string $step): ?array
    {
        $progress = $team->whatsapp_setup_progress ?? [];
        return $progress[$step] ?? null;
    }

    /**
     * Get all saved progress
     */
    public function getAllProgress(Team $team): array
    {
        return $team->whatsapp_setup_progress ?? [];
    }

    /**
     * Clear progress for a specific step
     */
    public function clearStep(Team $team, string $step): void
    {
        $progress = $team->whatsapp_setup_progress ?? [];
        unset($progress[$step]);

        $team->update(['whatsapp_setup_progress' => $progress]);
    }

    /**
     * Clear all progress
     */
    public function clearAll(Team $team): void
    {
        $team->update(['whatsapp_setup_progress' => null]);
    }

    /**
     * Check if team can resume setup
     */
    public function canResume(Team $team): bool
    {
        return !empty($team->whatsapp_setup_progress) &&
            $team->whatsapp_setup_in_progress;
    }

    /**
     * Get resume point (last successful step)
     */
    public function getResumePoint(Team $team): ?string
    {
        $progress = $team->whatsapp_setup_progress ?? [];

        if (empty($progress)) {
            return null;
        }

        // Get the most recent step
        $steps = array_keys($progress);
        return end($steps);
    }

    /**
     * Mark step as completed
     */
    public function markCompleted(Team $team, string $step): void
    {
        $this->saveProgress($team, $step, ['completed' => true]);
    }

    /**
     * Check if step is completed
     */
    public function isCompleted(Team $team, string $step): bool
    {
        $progress = $this->loadProgress($team, $step);
        return $progress['data']['completed'] ?? false;
    }

    /**
     * Get setup duration
     */
    public function getDuration(Team $team): ?int
    {
        if (!$team->whatsapp_setup_started_at) {
            return null;
        }

        $end = $team->whatsapp_setup_completed_at ?? now();
        return $team->whatsapp_setup_started_at->diffInSeconds($end);
    }

    /**
     * Store temporary data in cache (for OAuth callbacks, etc.)
     */
    public function storeTempData(Team $team, string $key, mixed $value, int $ttl = 600): void
    {
        $cacheKey = "whatsapp_setup:{$team->id}:{$key}";
        Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Retrieve temporary data from cache
     */
    public function getTempData(Team $team, string $key): mixed
    {
        $cacheKey = "whatsapp_setup:{$team->id}:{$key}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear temporary data
     */
    public function clearTempData(Team $team, string $key): void
    {
        $cacheKey = "whatsapp_setup:{$team->id}:{$key}";
        Cache::forget($cacheKey);
    }
}
