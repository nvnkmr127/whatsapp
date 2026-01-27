<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutomationService;
use App\Models\Team;
use App\Services\WhatsAppHealthMonitor;

class AuditBotRules extends Command
{
    protected $signature = 'audit:bot-rules';
    protected $description = 'Audit Bot/Automation Rules (Health Checks & Policies)';

    public function handle()
    {
        $this->info('Starting Bot Rules Audit...');

        // 1. Check for Health Integration in AutomationService
        $this->info("\n1. Checking Health Integration in AutomationService:");
        $serviceCode = file_get_contents(app_path('Services/AutomationService.php'));

        if (str_contains($serviceCode, 'WhatsAppHealthMonitor') || str_contains($serviceCode, 'checkHealth')) {
            $this->info("PASS: AutomationService appears to check health.");
        } else {
            $this->error("FAIL: AutomationService does NOT import or use WhatsAppHealthMonitor.");
            $this->line("   - Risk: Bots will try to reply even if WABA is BANNED or RESTRICTED.");
        }

        // 2. Check for Policy Service Integration (Free-form Window)
        $this->info("\n2. Checking Policy Service Integration:");
        if (str_contains($serviceCode, 'PolicyService')) {
            $this->info("PASS: PolicyService is used.");
        } else {
            $this->error("FAIL: PolicyService (24h Window) is NOT used in AutomationService.");
            $this->line("   - Risk: Bots will fail with 131047 errors when trying to reply outside 24h window, hurting quality score.");
        }

        $this->info("\nAudit Complete.");
    }
}
