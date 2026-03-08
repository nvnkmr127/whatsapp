<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Services\WorkflowEngine;
use Illuminate\Console\Command;

class CheckOnboardingWorkflows extends Command
{
    protected $signature = 'workflows:check-onboarding';
    protected $description = 'Check for incomplete onboarding and trigger workflows';

    public function handle(WorkflowEngine $engine)
    {
        $this->info('Checking onboarding workflows...');

        // 1. Check for "Incomplete Setup (24h)"
        // Users created > 24h ago, < 48h ago, with NO WABA connected
        $stalledTeams = Team::where('created_at', '<', now()->subHours(24))
            ->where('created_at', '>', now()->subHours(48))
            ->whereNull('whatsapp_business_account_id')
            ->get();

        foreach ($stalledTeams as $team) {
            // Check if we already ran this workflow for this team to avoid spam
            // (The engine handles idempotency if we design it right, but let's be safe)
            // Ideally, we check a "flag" or the workflow logs.
            // For now, we fire the event and let the engine decide based on logs.
            
            $engine->trigger('onboarding_incomplete_24h', $team->owner);
            $this->info("Triggered incomplete setup for Team {$team->id}");
        }

        // 2. Check for "No Activity (3 Days)"
        // Users created > 3 days ago, < 4 days ago, WITH WABA, but NO messages
        $inactiveTeams = Team::where('created_at', '<', now()->subDays(3))
            ->where('created_at', '>', now()->subDays(4))
            ->whereNotNull('whatsapp_business_account_id')
            ->whereNull('last_webhook_received_at') // Assuming this tracks activity
            ->get();

        foreach ($inactiveTeams as $team) {
            $engine->trigger('onboarding_no_activity_3d', $team->owner);
            $this->info("Triggered no activity for Team {$team->id}");
        }

        $this->info('Done.');
    }
}
