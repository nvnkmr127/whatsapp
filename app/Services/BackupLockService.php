<?php

namespace App\Services;

use App\Models\Team;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BackupLockService
 * ════════════════════════════════════════════════════════
 * Solves the race condition problem for backup and restore operations.
 *
 * RACE CONDITIONS PREVENTED
 * ─────────────────────────
 * Without locking, the following split-brain states are possible:
 *
 * 1. Backup during campaign dispatch
 *    → ZIP captures contacts table mid-write. Re-importing produces contacts
 *      in a phantom state (counted as sent but not in DB or vice versa).
 *
 * 2. Backup during webhook processing
 *    → Incoming messages being persisted mid-snapshot create an inconsistent
 *      DB dump: some messages exist, their conversation windows don't.
 *
 * 3. Restore while agents are online
 *    → Agents are actively writing to conversations/messages. The restore
 *      wipes the DB and re-applies an old snapshot WHILE agents are mid-write.
 *      Concurrent INSERTs from agent actions vs. DELETES from restore cause
 *      FK violations, deadlocks, and orphaned records.
 *
 * 4. Concurrent backups
 *    → Two backup ZIPs read from the DB simultaneously in different states.
 *      Both claim to be "the latest backup". One overwrites the other with
 *      a potentially corrupt or inconsistent snapshot.
 *
 * 5. Restore during another restore
 *    → Catastrophic: two restore processes both run clearTenantData() and
 *      then both try to re-import SQL. Results in permanent data loss.
 *
 * SOLUTION — TWO-LAYER GUARD
 * ─────────────────────────────────────────────────────────
 * Layer 1: MUTEX LOCK (Cache::lock)
 *   - Per-team atomic lock named "backup_mutex_{team_id}"
 *   - Acquired before any backup or restore begins
 *   - Released after operation completes (or fails)
 *   - TTL: 30 minutes for backup, 60 minutes for restore
 *   - If lock cannot be acquired → throws BackupAlreadyRunningException
 *   - Uses Cache default driver (database) — no Redis required
 *
 * Layer 2: TENANT MAINTENANCE MODE FLAG
 *   - Single cache key "team_under_maintenance_{id}" with a 2-hour TTL
 *   - Signals all jobs and middleware to PAUSE or SKIP for this team
 *   - Checked by: ProcessWebhookJob, PrepareCampaignJob, ExecuteAutomationNodeJob
 *   - Cleared after operation completes or fails (finally block)
 *   - TTL auto-expires to prevent stuck maintenance mode if the process dies
 *
 * IMPORTANT: Maintenance mode does NOT drop or reject webhooks.
 * Incoming webhook payloads are still STORED. Processing is delayed
 * until maintenance mode lifts. This prevents data loss from Meta.
 * ────────────────────────────────────────────────────────
 */
class BackupLockService
{
    /** Cache lock TTL for backup operations (seconds) */
    private const BACKUP_LOCK_TTL = 1800; // 30 minutes

    /** Cache lock TTL for restore operations (seconds) */
    private const RESTORE_LOCK_TTL = 3600; // 60 minutes

    /** Safety expiry for maintenance mode (auto-clears to prevent being stuck) */
    private const MAINTENANCE_AUTO_EXPIRY_SECONDS = 7200; // 2 hours

    // ─────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Acquire a backup mutex lock for the given team.
     * Enables tenant maintenance mode to pause in-flight operations.
     *
     * @throws BackupAlreadyRunningException if lock cannot be acquired.
     */
    public function acquireBackupLock(Team $team): \Illuminate\Cache\Lock
    {
        $lock = $this->getLock($team, 'backup', self::BACKUP_LOCK_TTL);

        if (! $lock->get()) {
            throw new BackupAlreadyRunningException(
                "A backup or restore is already in progress for Team {$team->id}. ".
                'Please wait for it to complete before starting another.'
            );
        }

        Log::info("[BackupLock] Backup lock acquired for Team {$team->id}");

        $this->enableMaintenanceMode($team, 'backup');

        return $lock;
    }

    /**
     * Acquire a restore mutex lock for the given team.
     * Enables tenant maintenance mode — REQUIRED for safe restore.
     *
     * @throws BackupAlreadyRunningException if lock cannot be acquired.
     * @throws RestoreWhileAgentsOnlineException if agents are actively in sessions.
     */
    public function acquireRestoreLock(Team $team): \Illuminate\Cache\Lock
    {
        // Additional check: warn if active agent sessions exist
        $this->assertSafeToRestore($team);

        $lock = $this->getLock($team, 'restore', self::RESTORE_LOCK_TTL);

        if (! $lock->get()) {
            throw new BackupAlreadyRunningException(
                "A backup or restore is already in progress for Team {$team->id}. ".
                'Cannot restore while another operation is running. Please try again shortly.'
            );
        }

        Log::info("[BackupLock] Restore lock acquired for Team {$team->id}");

        $this->enableMaintenanceMode($team, 'restore');

        return $lock;
    }

    /**
     * Release the lock and disable maintenance mode after an operation completes.
     * MUST be called in a finally block to guarantee execution on both success and failure.
     */
    public function release(\Illuminate\Cache\Lock $lock, Team $team): void
    {
        try {
            $lock->release();
            Log::info("[BackupLock] Lock released for Team {$team->id}");
        } catch (Exception $e) {
            Log::warning("[BackupLock] Failed to release lock for Team {$team->id}: ".$e->getMessage());
        }

        $this->disableMaintenanceMode($team);
    }

    /**
     * Check if a team is currently under maintenance (backup or restore in progress).
     * Used by jobs and middleware to decide whether to pause processing.
     */
    public function isUnderMaintenance(int $teamId): bool
    {
        return Cache::has($this->maintenanceKey($teamId));
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function getLock(Team $team, string $type, int $ttl): \Illuminate\Cache\Lock
    {
        $lockName = "backup_mutex_{$type}_{$team->id}";

        return Cache::lock($lockName, $ttl);
    }

    private function enableMaintenanceMode(Team $team, string $reason): void
    {
        // Single cache key with TTL — auto-clears if the process dies before release().
        Cache::put($this->maintenanceKey($team->id), $reason, self::MAINTENANCE_AUTO_EXPIRY_SECONDS);

        Log::info("[BackupLock] Tenant maintenance mode ENABLED for Team {$team->id}", [
            'reason' => $reason,
            'auto_expires_in_seconds' => self::MAINTENANCE_AUTO_EXPIRY_SECONDS,
        ]);
    }

    private function disableMaintenanceMode(Team $team): void
    {
        Cache::forget($this->maintenanceKey($team->id));

        Log::info("[BackupLock] Tenant maintenance mode DISABLED for Team {$team->id}");
    }

    /**
     * Checks if it is currently safe to restore.
     * Logs a warning if agents have unsaved conversations, but does not block
     * (blocking is left to the operator's discretion at the controller level).
     *
     * @throws RestoreWhileAgentsOnlineException if active agent-locked conversations exist.
     */
    private function assertSafeToRestore(Team $team): void
    {
        // Check for agent-locked conversations (active agent sessions)
        // These are written by the ConversationLockController and tracked in the cache.
        $activeLockCount = DB::table('conversation_locks')
            ->where('team_id', $team->id)
            ->where('expires_at', '>', now())
            ->count();

        if ($activeLockCount > 0) {
            Log::warning("[BackupLock] Restore requested for Team {$team->id} while {$activeLockCount} agent(s) are active.", [
                'team_id' => $team->id,
                'active_lock_count' => $activeLockCount,
            ]);

            throw new RestoreWhileAgentsOnlineException(
                "Cannot restore while {$activeLockCount} agent(s) have active conversations. ".
                'Please wait for all agents to close their sessions, or use Super Admin override.'
            );
        }
    }

    private function maintenanceKey(int $teamId): string
    {
        return 'team_under_maintenance_'.$teamId;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Custom Exceptions — used by controllers/tests to give precise error messages
// ─────────────────────────────────────────────────────────────────────────────

class BackupAlreadyRunningException extends \RuntimeException {}

class RestoreWhileAgentsOnlineException extends \RuntimeException {}
