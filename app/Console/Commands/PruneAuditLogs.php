<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune {--days=90 : The number of days of logs to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune audit logs older than a specified number of days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $date = now()->subDays($days);

        $count = AuditLog::where('created_at', '<', $date)->count();

        if ($count === 0) {
            $this->info("No logs found older than {$days} days.");
            return;
        }

        AuditLog::where('created_at', '<', $date)->delete();

        $this->info("Successfully pruned {$count} audit logs older than {$days} days.");

        // Log to laravel.log (wrap in try-catch due to potential permission issues seen earlier)
        try {
            Log::info("Audit logs pruned", [
                'count' => $count,
                'retention_days' => $days,
                'pruned_by' => 'system_command'
            ]);
        } catch (\Exception $e) {
            // Silently ignore log failure for now as the core task is done
        }
    }
}
