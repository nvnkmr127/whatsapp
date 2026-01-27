<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Services\BroadcastService;
use App\Services\WhatsAppHealthMonitor;
use App\Models\Campaign;

class AuditHealthSignals extends Command
{
    protected $signature = 'audit:health-signals';
    protected $description = 'Audit if Campaign Launch respects Health Signals (Quality, Limits)';

    public function handle()
    {
        $this->info('Starting Health Signals Audit...');

        // Mock a Team with RED quality
        $team = new Team([
            'id' => 99999,
            'name' => 'Audit Team',
            'whatsapp_quality_rating' => 'RED',
            'whatsapp_phone_status' => 'restricted',
            'whatsapp_messaging_limit' => 'TIER_1K',
            'whatsapp_access_token' => 'mock_token',
        ]);

        $monitor = app(WhatsAppHealthMonitor::class);
        $issues = $monitor->getBlockingIssues($team);

        $this->info("\n1. Health Monitor Check (Static):");
        if (in_array('Quality rating is RED', $issues) && in_array('Phone number restricted', $issues)) {
            $this->info("PASS: WhatsAppHealthMonitor correctly identifies blocking issues.");
        } else {
            $this->error("FAIL: WhatsAppHealthMonitor failed to identify issues. Found: " . implode(', ', $issues));
        }

        // Check BroadcastService integration
        // We can't easily mock the facade/service dependency injection in a simple command without complex setup,
        // so we will inspect the code logic via reflection or manual check representation.

        $this->info("\n2. BroadcastService Integration Check:");

        // Reflection to check if BroadcastService uses WhatsAppHealthMonitor
        $reflector = new \ReflectionClass(BroadcastService::class);
        $method = $reflector->getMethod('launch');
        $body = file_get_contents($reflector->getFileName());

        if (str_contains($body, 'WhatsAppHealthMonitor') || str_contains($body, 'canSendMessages') || str_contains($body, 'getBlockingIssues')) {
            $this->info("PASS warning: Found reference to Health/Blocking checks in BroadcastService (Verify logic manually).");
        } else {
            $this->error("FAIL: BroadcastService::launch does NOT appear to check WhatsAppHealthMonitor or quality status!");
            $this->line("   - Users might be able to launch campaigns even with RED quality.");
        }

        $this->info("\nAudit Complete.");
    }
}
