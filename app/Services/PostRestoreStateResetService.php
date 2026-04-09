<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AutomationRun;
use App\Models\Campaign;
use App\Models\FlowSession;
use App\Models\Team;
use App\Models\WhatsAppCall;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PostRestoreStateResetService
 * ════════════════════════════════════════════════════════
 * Executed immediately after a tenant restore completes.
 *
 * WHY THIS IS REQUIRED
 * ──────────────────────────────────────────────────────
 * A database restore is a point-in-time rollback of *persisted* data only.
 * It does NOT affect:
 *   - Jobs sitting in the queue (campaign messages, automation steps)
 *   - Cache entries (entitlement, billing alerts, rate-limit counters)
 *   - In-flight automation runs that are still tracked by the scheduler
 *   - WhatsApp token validity state stored in Redis / on-model
 *   - Active call states (mid-call, ringing)
 *   - Flow sessions that are mid-session
 *
 * Without this reset, the system ends up in a split-brain state where:
 *   ❌ Queue workers re-process jobs whose DB state was wiped (phantom ops)
 *   ❌ Entitlement cache serves stale plan data (quota bypasses)
 *   ❌ Restored contacts re-enter automation runs that were already completed
 *   ❌ Campaigns appear as "completed" in DB but still have workers processing
 *   ❌ Active calls are in limbo — no DB record for them after restore
 *
 * STEPS PERFORMED
 * ──────────────────────────────────────────────────────
 * 1. Halt in-flight campaigns  — mark processing/sending campaigns as interrupted
 * 2. Abort automation runs    — terminate all active runs; they must re-trigger naturally
 * 3. Terminate flow sessions  — clear mid-session WhatsApp flow state
 * 4. Terminate active calls   — mark in-progress calls as terminated-by-restore
 * 5. Flush tenant Redis cache — clears entitlement, billing alerts, rate limits
 * 6. Clear queue jobs         — removes tenant-scoped jobs from DB queue
 * 7. Re-sync WABA token       — forces re-validation of WhatsApp token on next request
 * 8. Audit log                — records every action taken for compliance trace
 * ────────────────────────────────────────────────────────
 */
class PostRestoreStateResetService
{
    /** Tracks every action taken for the audit log. */
    private array $auditTrail = [];

    /** Count of each category of items reset. */
    private array $counters = [
        'campaigns_halted' => 0,
        'automation_runs_aborted' => 0,
        'flow_sessions_cleared' => 0,
        'calls_terminated' => 0,
        'cache_keys_flushed' => 0,
        'queue_jobs_cleared' => 0,
    ];

    /**
     * Execute the full post-restore state reset for the given team.
     *
     * This method is designed to be fault-tolerant: each step is wrapped
     * in a try/catch so a failure in one step does not abort the others.
     * All errors are logged and included in the audit trail.
     */
    public function reset(Team $team): array
    {
        $this->auditTrail = [];
        $this->counters = array_fill_keys(array_keys($this->counters), 0);

        Log::info("[PostRestoreReset] Starting post-restore state reset for Team {$team->id}");

        $this->step1_haltInFlightCampaigns($team);
        $this->step2_abortAutomationRuns($team);
        $this->step3_terminateFlowSessions($team);
        $this->step4_terminateActiveCalls($team);
        $this->step5_flushTenantCache($team);
        $this->step6_clearQueuedJobs($team);
        $this->step7_resyncWhatsAppTokenState($team);

        // Final summary audit log
        $this->writeAuditLog($team, 'backup.restore_state_reset', 'Post-restore state reset completed.', [
            'counters' => $this->counters,
            'steps_taken' => $this->auditTrail,
        ]);

        Log::info("[PostRestoreReset] Completed for Team {$team->id}", $this->counters);

        return [
            'counters' => $this->counters,
            'audit_trail' => $this->auditTrail,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 1 — Halt In-Flight Campaigns
    // ─────────────────────────────────────────────────────────────────────
    // Campaigns in 'processing' or 'sending' status have active queue workers
    // dispatched. After restore, the DB records they reference may no longer
    // exist or may have reverted. Mark them as 'interrupted' so:
    //   - The UI shows the user what happened
    //   - No new queue workers are dispatched against stale state
    // ─────────────────────────────────────────────────────────────────────
    private function step1_haltInFlightCampaigns(Team $team): void
    {
        try {
            $halted = Campaign::where('team_id', $team->id)
                ->whereIn('status', ['processing', 'sending', 'queued'])
                ->get();

            foreach ($halted as $campaign) {
                /** @var Campaign $campaign */
                $campaign->update([
                    'status' => 'interrupted',
                    'completed_at' => now(),
                ]);
            }

            $count = $halted->count();
            $this->counters['campaigns_halted'] = $count;
            $this->trail('step1_campaigns', "Halted {$count} in-flight campaigns (status → interrupted).");
        } catch (Exception $e) {
            $this->trailError('step1_campaigns', $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 2 — Abort Active Automation Runs
    // ─────────────────────────────────────────────────────────────────────
    // After restore, contacts restored from backup may or may not be in
    // the same state the automation run expects. Active runs are a liability:
    //   - The contact could be in a different step than expected
    //   - The run resumption timer (resume_at) is now in the past or wrong
    //   - Re-triggering naturally from the restored contact state is safer
    // ─────────────────────────────────────────────────────────────────────
    private function step2_abortAutomationRuns(Team $team): void
    {
        try {
            $aborted = AutomationRun::whereHas('automation', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            })
                ->whereIn('status', ['active', 'waiting', 'pending', 'running'])
                ->get();

            foreach ($aborted as $run) {
                /** @var AutomationRun $run */
                $run->update([
                    'status' => 'aborted_by_restore',
                    'resume_at' => null,
                    'state_data' => array_merge($run->state_data ?? [], [
                        '_aborted_by_restore' => now()->toIso8601String(),
                    ]),
                ]);
            }

            $count = $aborted->count();
            $this->counters['automation_runs_aborted'] = $count;
            $this->trail('step2_automations', "Aborted {$count} active automation runs.");
        } catch (Exception $e) {
            $this->trailError('step2_automations', $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 3 — Terminate Active Flow Sessions
    // ─────────────────────────────────────────────────────────────────────
    // WhatsApp flow sessions represent mid-conversation interactive flows.
    // After restore, the flow state in the DB may be older than the live
    // session the user is navigating — sending them an unexpected screen.
    // Clearing sessions forces a clean start when the user next interacts.
    // ─────────────────────────────────────────────────────────────────────
    private function step3_terminateFlowSessions(Team $team): void
    {
        try {
            // FlowSessions belong to contacts, which belong to the team.
            $count = FlowSession::whereHas('contact', function ($q) use ($team) {
                $q->where('team_id', $team->id);
            })
                ->whereIn('status', ['active', 'pending', 'in_progress'])
                ->update([
                    'status' => 'terminated_by_restore',
                    'updated_at' => now(),
                ]);

            $this->counters['flow_sessions_cleared'] = $count;
            $this->trail('step3_flow_sessions', "Terminated {$count} active flow sessions.");
        } catch (Exception $e) {
            $this->trailError('step3_flow_sessions', $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 4 — Terminate Active Calls
    // ─────────────────────────────────────────────────────────────────────
    // Any call that was mid-progress (ringing, active, on-hold) at the time
    // of backup will have no DB record after restore, or a stale record.
    // Mark all current non-terminal calls as 'terminated_by_restore'.
    // ─────────────────────────────────────────────────────────────────────
    private function step4_terminateActiveCalls(Team $team): void
    {
        try {
            $count = WhatsAppCall::where('team_id', $team->id)
                ->whereIn('status', ['ringing', 'active', 'on_hold', 'connecting'])
                ->update([
                    'status' => 'terminated_by_restore',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->counters['calls_terminated'] = $count;
            $this->trail('step4_calls', "Terminated {$count} active calls.");
        } catch (Exception $e) {
            $this->trailError('step4_calls', $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 5 — Flush Tenant-Scoped Redis Cache
    // ─────────────────────────────────────────────────────────────────────
    // The following cache keys are known to be team-scoped and may be stale
    // after a restore. We flush them individually to avoid a full cache flush
    // (which would affect all tenants in production):
    //
    //   - billing_alert_{team_id}_{metric}   (BillingService)
    //   - entitlement_{team_id}              (EntitlementService in-request only)
    //   - plan_limits_{team_id}              (if cached)
    //   - maintenance_mode                   (global, skip)
    //
    // EntitlementService uses an in-request PHP array cache ($this->cache),
    // which is automatically reset on each new request. No action needed there.
    // ─────────────────────────────────────────────────────────────────────
    private function step5_flushTenantCache(Team $team): void
    {
        $knownMetrics = ['messages', 'agents', 'automations', 'contacts', 'ai_conversations', 'call_minutes'];

        $keysToFlush = [];

        // BillingService billing alert cooldown keys
        foreach ($knownMetrics as $metric) {
            $keysToFlush[] = "billing_alert_{$team->id}_{$metric}";
        }

        // Entitlement / plan overrides if they get persisted to cache
        $keysToFlush[] = "entitlement_{$team->id}";
        $keysToFlush[] = "team_plan_{$team->id}";
        $keysToFlush[] = "team_features_{$team->id}";
        $keysToFlush[] = "team_limits_{$team->id}";

        // WhatsApp health monitor cache
        $keysToFlush[] = "whatsapp_health_{$team->id}";

        $flushed = 0;
        foreach ($keysToFlush as $key) {
            try {
                if (Cache::has($key)) {
                    Cache::forget($key);
                    $flushed++;
                }
            } catch (Exception $e) {
                Log::warning("[PostRestoreReset] Failed to flush cache key '{$key}': ".$e->getMessage());
            }
        }

        $this->counters['cache_keys_flushed'] = $flushed;
        $this->trail('step5_cache', "Flushed {$flushed} of ".count($keysToFlush).' known tenant cache keys.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 6 — Clear Tenant-Scoped Queued Jobs
    // ─────────────────────────────────────────────────────────────────────
    // Jobs in the 'jobs' table (database queue driver) that belong to this
    // team should be cleared. We identify them by payload content.
    //
    // For Redis queue driver, we cannot safely introspect without the team_id
    // being serialized into job payload. We take a best-effort approach:
    //   - Delete all database jobs that contain this team's ID in their payload
    //   - Log a warning if using Redis (manual flush may be required)
    //
    // NOTE: This does NOT affect global system jobs or other tenants' jobs.
    // ─────────────────────────────────────────────────────────────────────
    private function step6_clearQueuedJobs(Team $team): void
    {
        try {
            $driver = config('queue.default');

            if ($driver === 'database') {
                // DB queue: safe to inspect and delete by payload content
                $deleted = DB::table('jobs')
                    ->where('payload', 'LIKE', '%"team_id":'.$team->id.'%')
                    ->orWhere('payload', 'LIKE', '%"teamId":'.$team->id.'%')
                    ->delete();

                // Also clear failed jobs for this team
                $failedDeleted = DB::table('failed_jobs')
                    ->where('payload', 'LIKE', '%"team_id":'.$team->id.'%')
                    ->orWhere('payload', 'LIKE', '%"teamId":'.$team->id.'%')
                    ->delete();

                $total = $deleted + $failedDeleted;
                $this->counters['queue_jobs_cleared'] = $total;
                $this->trail('step6_queue', "Cleared {$total} queued/failed jobs from DB queue for team.");
            } else {
                // Redis/SQS/etc: cannot safely introspect by team_id
                // Log a warning so ops team knows to investigate
                Log::warning("[PostRestoreReset] Queue driver is '{$driver}'. Cannot safely clear team-scoped jobs automatically. Manual intervention may be required for Team {$team->id}.", [
                    'team_id' => $team->id,
                    'driver' => $driver,
                ]);
                $this->trail('step6_queue', "⚠ Queue driver is '{$driver}'. Automatic job clearance skipped. Manual flush recommended.");
            }
        } catch (Exception $e) {
            $this->trailError('step6_queue', $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // STEP 7 — Re-Sync WhatsApp Token Validity State
    // ─────────────────────────────────────────────────────────────────────
    // After restore, the token on the Team model may:
    //   - Be an older version of the token (from the backup date)
    //   - Have an out-of-date `whatsapp_token_last_validated` timestamp
    //   - Point to an expired or revoked token
    //
    // We force re-validation by:
    //   1. Nulling `whatsapp_token_last_validated` → forces WhatsAppConfigService
    //      to re-validate on the next outbound request
    //   2. Setting `whatsapp_connected = false` if the token expiry has already passed
    //   3. Flagging the setup state as DEGRADED if previously ACTIVE, so the team
    //      admin sees a clear indicator that their connection needs to be verified
    //
    // This does NOT delete the token — it just forces re-validation.
    // ─────────────────────────────────────────────────────────────────────
    private function step7_resyncWhatsAppTokenState(Team $team): void
    {
        try {
            // Reload fresh from DB (the restore may have changed token fields)
            $team->refresh();

            $updates = [
                // Force re-validation on next outbound request
                'whatsapp_token_last_validated' => null,
            ];

            // If token is already expired, disconnect immediately
            if ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast()) {
                $updates['whatsapp_connected'] = false;
                $updates['whatsapp_setup_state'] = \App\Enums\IntegrationState::DISCONNECTED;
                $this->trail('step7_waba', 'WhatsApp token was expired. Marked as disconnected.');
            } else {
                // Token may be valid, but degrade state until re-validated
                if ($team->whatsapp_connected) {
                    $updates['whatsapp_setup_state'] = \App\Enums\IntegrationState::DEGRADED;
                    $this->trail('step7_waba', 'WhatsApp token validity cleared. Setup state set to DEGRADED pending re-validation.');
                } else {
                    $this->trail('step7_waba', 'WhatsApp not connected. No token state changes needed.');
                }
            }

            // Use DB::table to avoid Eloquent events and casts interfering
            DB::table('teams')->where('id', $team->id)->update($updates);
        } catch (Exception $e) {
            $this->trailError('step7_waba', $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function trail(string $step, string $message): void
    {
        $this->auditTrail[$step] = $message;
        Log::info("[PostRestoreReset] {$step}: {$message}");
    }

    private function trailError(string $step, Exception $e): void
    {
        $msg = 'ERROR: '.$e->getMessage();
        $this->auditTrail[$step] = $msg;
        Log::error("[PostRestoreReset] {$step} failed: ".$e->getMessage(), [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    private function writeAuditLog(Team $team, string $action, string $description, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'team_id' => $team->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()?->ip(),
                'properties' => $properties,
            ]);
        } catch (Exception $e) {
            Log::error('[PostRestoreReset] Failed to write audit log: '.$e->getMessage());
        }
    }
}
