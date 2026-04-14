<?php

namespace App\Console\Commands;

use App\Models\WebhookPayload;
use App\Models\WhatsAppSetupAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneWhatsAppLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:prune {--days=30 : The number of days of logs to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old WhatsApp webhook payloads and setup audits to prevent database bloat.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $date = now()->subDays($days);

        // 1. Prune Webhook Payloads
        $payloadCount = WebhookPayload::where('created_at', '<', $date)->count();
        if ($payloadCount > 0) {
            WebhookPayload::where('created_at', '<', $date)->delete();
            $this->info("Successfully pruned {$payloadCount} webhook payloads.");
        }

        // 2. Prune Setup Audits
        $auditCount = WhatsAppSetupAudit::where('created_at', '<', $date)->count();
        if ($auditCount > 0) {
            WhatsAppSetupAudit::where('created_at', '<', $date)->delete();
            $this->info("Successfully pruned {$auditCount} setup audit logs.");
        }

        if ($payloadCount === 0 && $auditCount === 0) {
            $this->info("No WhatsApp logs found older than {$days} days.");
            return;
        }

        try {
            Log::info('WhatsApp logs pruned', [
                'payloads_count' => $payloadCount,
                'audits_count' => $auditCount,
                'retention_days' => $days,
            ]);
        } catch (\Exception $e) {
            // Silently ignore
        }
    }
}
