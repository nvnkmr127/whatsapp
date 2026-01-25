<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailLog;

class EmailLogCleanup extends Command
{
    protected $signature = 'email:cleanup-logs {--days=30 : The number of days of logs to retain}';
    protected $description = 'Remove email logs older than a certain number of days';

    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Cleaning up email logs older than {$days} days...");

        $count = EmailLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Successfully deleted {$count} old logs.");
    }
}
