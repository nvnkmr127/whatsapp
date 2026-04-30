<?php

namespace App\Console\Commands;

use App\Models\Contact;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:check {hours=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for unanswered messages exceeding the SLA time limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Enterprise SLA check...');

        $result = app(\App\Services\SlaService::class)->checkBreaches();

        if ($result['breached'] > 0 || $result['warning'] > 0) {
            $this->warn("SLA Check Complete: Found {$result['breached']} breaches and {$result['warning']} warnings.");
        } else {
            $this->info('SLA Check Complete: No new breaches detected.');
        }

        return self::SUCCESS;
    }
}
