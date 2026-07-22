<?php

namespace App\Console\Commands;

use App\Models\WhatsAppHealthSnapshot;
use Illuminate\Console\Command;

class PruneHealthSnapshots extends Command
{
    protected $signature = 'whatsapp:prune-health-snapshots {--days=180 : The number of days of snapshots to retain}';

    protected $description = 'Prune WhatsApp health snapshots older than a specified number of days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        // Snapshots land every 30 minutes per team (~17.5k rows/team/year), so
        // this table grows faster than anything else in the health stack.
        // withoutGlobalScopes: TenantScope no-ops in console, but be explicit.
        $count = WhatsAppHealthSnapshot::withoutGlobalScopes()
            ->where('snapshot_at', '<', $cutoff)
            ->delete();

        $this->info($count === 0
            ? "No snapshots older than {$days} days."
            : "Pruned {$count} health snapshots older than {$days} days.");

        return self::SUCCESS;
    }
}
