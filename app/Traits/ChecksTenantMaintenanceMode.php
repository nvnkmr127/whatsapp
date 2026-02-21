<?php

namespace App\Traits;

use App\Services\BackupLockService;
use Illuminate\Support\Facades\Log;

/**
 * ChecksTenantMaintenanceMode — Job Guard Trait
 * ════════════════════════════════════════════════════════
 * Add this trait to any job that writes to tenant data or dispatches
 * downstream actions. It allows the job to self-abort gracefully when
 * the team is under backup/restore maintenance mode.
 *
 * USAGE (in any ShouldQueue job):
 * ─────────────────────────────────────────────────────────
 *   use App\Traits\ChecksTenantMaintenanceMode;
 *
 *   public function handle(): void
 *   {
 *       if ($this->isTeamUnderMaintenance($this->team->id)) {
 *           return; // Silently defer — job is released back to queue or deleted
 *       }
 *       // ... main job logic
 *   }
 *
 * BEHAVIOUR
 * ─────────────────────────────────────────────────────────
 * - If the team is under maintenance:
 *     → For WEBHOOK jobs: releases back to queue with a 60-second delay
 *       (preserves incoming data — does not drop it)
 *     → For CAMPAIGN/AUTOMATION jobs: deletes the job (restore resets state anyway)
 *     → Behaviour is controlled by passing $releaseOrDelete parameter
 *
 * RELEASE vs DELETE
 * ─────────────────────────────────────────────────────────
 * - RELEASE: Job goes back in queue, retried after delay. Use for data ingestion
 *   jobs where losing the payload would cause data loss (e.g., webhooks).
 * - DELETE: Job is discarded. Use for outbound/processing jobs where the
 *   PostRestoreStateResetService will re-establish correct state anyway.
 * ────────────────────────────────────────────────────────
 */
trait ChecksTenantMaintenanceMode
{
    /**
     * Check if a team is under backup/restore maintenance.
     * If yes, releases or deletes the current job and returns true.
     * The caller must treat a true return as a hard stop (return immediately).
     *
     * @param int    $teamId           The team to check
     * @param string $releaseOrDelete  'release' (re-queue) or 'delete' (discard silently)
     * @param int    $releaseDelay     Seconds to wait before re-processing (only for release)
     * @return bool  True if under maintenance (job was released/deleted). False if safe to proceed.
     */
    protected function isTeamUnderMaintenance(
        int $teamId,
        string $releaseOrDelete = 'delete',
        int $releaseDelay = 60
    ): bool {
        /** @var BackupLockService $lockService */
        $lockService = app(BackupLockService::class);

        if (!$lockService->isUnderMaintenance($teamId)) {
            return false;
        }

        $jobClass = static::class;

        Log::info("[Job Guard] Team {$teamId} is under maintenance. Job {$jobClass} will be {$releaseOrDelete}d.", [
            'team_id' => $teamId,
            'job' => $jobClass,
            'action' => $releaseOrDelete,
        ]);

        if ($releaseOrDelete === 'release') {
            // Re-queue with delay — data is preserved, will reprocess after maintenance ends
            $this->release($releaseDelay);
        } else {
            // Silently delete — PostRestoreStateResetService handles state cleanup
            $this->delete();
        }

        return true;
    }
}
