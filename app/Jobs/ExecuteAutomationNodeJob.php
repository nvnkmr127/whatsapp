<?php

namespace App\Jobs;

use App\Models\AutomationRun;
use App\Models\AutomationStepLedger;
use App\Services\AutomationService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExecuteAutomationNodeJob implements ShouldQueue
{
    use \App\Traits\ChecksTenantMaintenanceMode;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $runId;

    public $nodeId;

    public $attempt;

    public $tries = 3;

    public $backoff = [10, 60, 300];

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    /**
     * Create a new job instance.
     */
    public function __construct(int $runId, string $nodeId, int $attempt = 1)
    {
        $this->runId = $runId;
        $this->nodeId = $nodeId;
        $this->attempt = $attempt;
        $this->onQueue('messages'); // Prioritize automation execution
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        Log::info("ExecuteAutomationNodeJob started: Run #{$this->runId}, Node #{$this->nodeId}, Attempt #{$this->attempt}");
        // 1. Atomic Claim & Heartbeat
        // This prevents multiple jobs from processing the same run simultaneously
        // and provides a timestamp for crash recovery detection.
        $updated = AutomationRun::where('id', $this->runId)
            ->whereIn('status', ['active', 'executing'])
            ->update([
                'status' => 'executing',
                'last_processed_at' => now(),
            ]);

        if (! $updated) {
            Log::warning("Atomic claim failed for Run #{$this->runId}. Job aborting.");

            return;
        }

        $run = AutomationRun::with('automation', 'contact')->find($this->runId);

        if (! $run) {
            Log::warning("AutomationRun #{$this->runId} not found in database.");

            return;
        }

        // ─────────────────────────────────────────────────────────────
        // MAINTENANCE GUARD: If team is under backup/restore, discard this job.
        // PostRestoreStateResetService aborts all runs — no double-processing.
        // ─────────────────────────────────────────────────────────────
        if ($this->isTeamUnderMaintenance($run->automation->team_id, 'delete')) {
            return;
        }

        // 2. Deterministic State Check — MUST run before the idempotency short-circuit below.
        // A stale/redelivered job for an already-completed node would otherwise advance the
        // flow from wherever the run currently sits, skipping nodes and duplicating messages.
        if ($run->state_data['current_node_id'] !== $this->nodeId) {
            Log::warning("Divergence detected: Run #{$this->runId} expected node {$run->state_data['current_node_id']}, Job ordered {$this->nodeId}. Aborting stale job.");

            return;
        }

        // 3. Idempotency Check via Ledger — pointer still on this node but it already succeeded
        // (e.g. crashed after the ledger write but before advancing). Safe to move forward.
        $executionKey = "{$this->runId}_{$this->nodeId}";
        $ledgerEntry = AutomationStepLedger::where('execution_key', $executionKey)->first();
        if ($ledgerEntry && $ledgerEntry->status === 'success') {
            Log::info("Node {$this->nodeId} already succeeded for run {$this->runId}. Advancing.");
            $this->dispatchNext($run);

            return;
        }

        // 3. Execution with Transactional Ledger Logging
        try {
            // Use Container to resolve dependencies (HealthMonitor, PolicyService)
            $service = app(AutomationService::class);

            DB::transaction(function () use ($service, $run) {
                // Increment version for optimistic locking if needed
                $run->increment('version');
                $run->increment('step_count');

                if ($run->step_count > config('automation.max_steps', 50)) {
                    throw new \Exception('Max step limit reached for safety.');
                }

                // Execute logic
                $service->executeNodeSync($run);
            });

        } catch (\Exception $e) {
            Log::error("Automation Step Error #{$this->runId} Node {$this->nodeId}: ".$e->getMessage());

            // Record failure in ledger
            $executionKey = "{$this->runId}_{$this->nodeId}";
            AutomationStepLedger::updateOrCreate(
                ['execution_key' => $executionKey],
                [
                    'automation_run_id' => $this->runId,
                    'node_id' => $this->nodeId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]
            );

            throw $e; // Trigger retry
        }
    }

    protected function dispatchNext(AutomationRun $run)
    {
        $service = app(AutomationService::class);
        $service->moveToNextNode($run);
    }
}
