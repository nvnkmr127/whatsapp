<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutomationService;

class ResumeDelayedAutomations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:resume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume paused automations (e.g. Abandoned Cart Follow-ups)';

    /**
     * Execute the console command.
     */
    public function handle(AutomationService $service)
    {
        $this->info('Checking for due delayed automations...');
        $service->resumeScheduledRuns();
        $this->info('Processed.');
    }
}
