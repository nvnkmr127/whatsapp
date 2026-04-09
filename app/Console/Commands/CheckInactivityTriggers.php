<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\Contact;
use App\Services\AutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInactivityTriggers extends Command
{
    protected $signature = 'automations:check-inactivity';

    protected $description = 'Check and fire inactivity automation triggers';

    public function handle()
    {
        $this->info('Checking inactivity triggers...');

        // Find all active automations with inactivity trigger
        $automations = Automation::where('is_active', true)
            ->where('trigger_type', 'inactivity')
            ->get();

        if ($automations->isEmpty()) {
            $this->info('No active inactivity automations found.');

            return;
        }

        $service = app(AutomationService::class);

        foreach ($automations as $automation) {
            $days = (int) ($automation->trigger_config['days'] ?? 7);
            if ($days <= 0) {
                continue;
            }

            $threshold = now()->subDays($days);

            // Find contacts who haven't interacted in X days and haven't run THIS automation recently
            Contact::where('team_id', $automation->team_id)
                ->where(function ($q) use ($threshold) {
                    $q->whereNull('last_interaction_at')
                        ->orWhere('last_interaction_at', '<', $threshold);
                })
                ->whereDoesntHave('automationRuns', function ($q) use ($automation) {
                    $q->where('automation_id', $automation->id)
                        ->where('created_at', '>', now()->subHours(24)); // Don't re-trigger within 24h
                })
                ->chunk(100, function ($contacts) use ($service, $automation) {
                    foreach ($contacts as $contact) {
                        try {
                            $service->start($automation, $contact);
                            $this->info("Triggered inactivity automation #{$automation->id} for contact #{$contact->id}");
                        } catch (\Exception $e) {
                            Log::error("Inactivity trigger failed for contact {$contact->id} in automation {$automation->id}: ".$e->getMessage());
                        }
                    }
                });
        }

        $this->info('Finished checking inactivity triggers.');
    }
}
