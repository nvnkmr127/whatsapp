<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Models\WorkflowLog;
use App\Services\WorkflowEngine;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteWorkflowNodesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $workflow;

    public $nodes;

    public $subject;

    public $context;

    public $log;

    public $tries = 3;

    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Workflow $workflow, array $nodes, Model $subject, array $context = [], ?WorkflowLog $log = null)
    {
        $this->workflow = $workflow;
        $this->nodes = $nodes;
        $this->subject = $subject;
        $this->context = $context;
        $this->log = $log;

        $this->onQueue('workflows'); // Worker Scaling -> Dedicated Queue
    }

    /**
     * Execute the job.
     */
    public function handle(WorkflowEngine $engine): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        if (! $this->subject) {
            Log::warning('Workflow Execute Nodes Job: Subject deleted or missing, skipping.');

            return;
        }

        if ($this->log && $this->log->status === 'waiting') {
            $this->log->update(['status' => 'running']);
        }

        // Access internal method to continue execution
        $reflectionEngine = new \ReflectionClass($engine);
        $method = $reflectionEngine->getMethod('executeNodes');
        $method->setAccessible(true);

        $method->invokeArgs($engine, [
            $this->nodes,
            $this->subject,
            $this->context,
            $this->workflow,
            $this->log,
        ]);
    }
}
