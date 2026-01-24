<?php

namespace App\Console\Commands;

use App\Models\SystemEvent;
use Illuminate\Console\Command;

class PruneSystemEvents extends Command
{
    protected $signature = 'events:prune';
    protected $description = 'Prune old system events based on retention policy';

    public function handle()
    {
        $this->info("Starting System Event Pruning...");

        // 1. Prune Debug/Noise (7 Days)
        $count = SystemEvent::where('category', 'debug')
            ->where('occurred_at', '<', now()->subDays(7))
            ->delete();
        $this->info("Deleted {$count} Debug events.");

        // 2. Prune Operational (30 Days)
        $count = SystemEvent::where('category', 'operational')
            ->where('occurred_at', '<', now()->subDays(30))
            ->delete();
        $this->info("Deleted {$count} Operational events.");

        // 3. Prune Business (1 Year - Optional active tables purge)
        // Usually we keep Business events longer, maybe move to cold storage instead of delete.
        // For active table performance, we might prune after 1 year.
        $count = SystemEvent::where('category', 'business')
            ->where('occurred_at', '<', now()->subYear())
            ->delete();
        $this->info("Deleted {$count} old Business events.");

        $this->info("Pruning Complete.");
    }
}
