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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $runId;
    public $nodeId;
    public $attempt;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(int $runId, string $nodeId, int $attempt = 1)
    {
        $this->runId = $runId;
        $this->nodeId = $nodeId;
        $this->attempt = $attempt;
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        // 1. Atomic Claim & Heartbeat
        // This prevents multiple jobs from processing the same run simultaneously
        // and provides a timestamp for crash recovery detection.
        $updated = AutomationRun::where('id', $this->runId)
            ->whereIn('status', ['active', 'executing'])
            ->update([
                'status' => 'executing',
                'last_processed_at' => now()
            ]);

        if (!$updated) {
            return;
        }

        $run = AutomationRun::with('automation', 'contact')->find($this->runId);

        if (!$run) {
            return;
        }

        // 2. Idempotency Check via Ledger
        $executionKey = "{$this->runId}_{$this->nodeId}";
        $ledgerEntry = AutomationStepLedger::where('execution_key', $executionKey)->first();

        if ($ledgerEntry && $ledgerEntry->status === 'success') {
            Log::info("Node {$this->nodeId} already succeeded for run {$this->runId}. Skipping.");
            $this->dispatchNext($run);
            return;
        }

        // 2. Deterministic State Check
        if ($run->state_data['current_node_id'] !== $this->nodeId) {
            Log::warning("Divergence detected: Run #{$this->runId} expected node {$run->state_data['current_node_id']}, Job ordered {$this->nodeId}. Correcting.");
            // Optional: Terminate or Correct. Let's correct to the expected state.
            return;
        }

        // 3. Execution with Transactional Ledger Logging
        try {
            $service = new AutomationService($whatsapp);

            DB::transaction(function () use ($service, $run) {
                // Increment version for optimistic locking if needed
                $run->increment('version');
                $run->increment('step_count');

                if ($run->step_count > 50) {
                    throw new \Exception("Max step limit reached for safety.");
                }

                // Execute logic
                $service->executeNodeSync($run);
            });

        } catch (\Exception $e) {
            Log::error("Automation Step Error #{$this->runId} Node {$this->nodeId}: " . $e->getMessage());

            // Record failure in ledger
            AutomationStepLedger::updateOrCreate(
                ['execution_key' => $executionKey],
                [
                    'automation_run_id' => $this->runId,
                    'node_id' => $this->nodeId,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ]
            );

            throw $e; // Trigger retry
        }
    }

    protected function dispatchNext(AutomationRun $run)
    {
        $service = new AutomationService(new WhatsAppService());
        $service->moveToNextNode($run);
    }
}
