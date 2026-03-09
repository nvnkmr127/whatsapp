<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Workflow;
use App\Services\WorkflowEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RunScheduledWorkflows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflows:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all active time-triggered workflows matching current time logic';

    /**
     * Execute the console command.
     */
    public function handle(WorkflowEngine $engine)
    {
        $now = now();
        $this->info("Checking Scheduled Workflows at {$now}");

        // Gather all time-based workflows
        $workflows = Workflow::where('is_active', true)
            ->whereIn('trigger_type', ['run_daily', 'run_weekly', 'run_monthly', 'schedule_workflow'])
            ->get();

        $executedCount = 0;

        foreach ($workflows as $workflow) {
            /** @var \App\Models\Workflow $workflow */
            $config = $workflow->trigger_config ?? [];
            if (empty($config['time'])) {
                continue; // Requires at least a time
            }

            // Check Time: Does current H:i match config Time?
            // (Scheduled via * * * * * cron, so matches precision up to minutes)
            if ($now->format('H:i') !== Carbon::parse($config['time'])->format('H:i')) {
                continue;
            }

            $shouldRun = false;

            switch ($workflow->trigger_type) {
                case 'run_daily':
                    $shouldRun = true;
                    break;
                case 'run_weekly':
                    $dayOfWeek = $config['day'] ?? null;
                    if ($dayOfWeek !== null && $now->dayOfWeek == $dayOfWeek) {
                        $shouldRun = true;
                    }
                    break;
                case 'run_monthly':
                    $dayOfMonth = $config['day'] ?? null;
                    if ($dayOfMonth !== null && $now->day == $dayOfMonth) {
                        $shouldRun = true;
                    }
                    break;
                case 'schedule_workflow':
                    $date = $config['date'] ?? null;
                    if ($date !== null && $now->format('Y-m-d') === $date) {
                        $shouldRun = true;
                        // For one-off, we could auto-disable the workflow after triggering it
                        // $workflow->update(['is_active' => false]);
                    }
                    break;
            }

            if ($shouldRun) {
                $this->info("Triggering Workflow #{$workflow->id}: {$workflow->name}");

                // For a scheduled workflow, we typically run it universally without a single external event. 
                // We'd need to iterate over eligible targets. 
                // E.g. Run this workflow against all Contacts in the team, or a segment.
                // For now, let's grab the Team's first 50 contacts as a conceptual batch (In a real system you'd queue jobs per contact depending on segment).

                $contacts = Contact::where('team_id', $workflow->team_id)->take(50)->get();
                foreach ($contacts as $contact) {
                    // Trigger the engine reflection exactly like the webhook does
                    $reflectionEngine = new \ReflectionClass($engine);
                    $method = $reflectionEngine->getMethod('executeWorkflow');
                    $method->setAccessible(true);
                    $method->invokeArgs($engine, [$workflow, $contact, ['scheduled_run' => true]]);
                }

                $executedCount++;
            }
        }

        $this->info("Executed {$executedCount} scheduled workflows.");
        return Command::SUCCESS;
    }
}
