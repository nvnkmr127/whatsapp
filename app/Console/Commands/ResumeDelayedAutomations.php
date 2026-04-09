<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    public function handle(AutomationService $service): int
    {
        try {
            $this->info('Checking for due delayed automations...');
            $service->resumeScheduledRuns();
            $this->info('Processed.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('automation:resume command failed', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            $this->error('Automation resume failed. Check logs for details.');

            return self::FAILURE;
        }
    }
}
