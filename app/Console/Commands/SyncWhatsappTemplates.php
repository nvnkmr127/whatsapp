<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\TemplateService;
use Illuminate\Console\Command;

class SyncWhatsappTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:sync-templates {team_id? : Optional Team ID to sync specific team}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync WhatsApp Templates from Meta for all teams or a specific team.';

    /**
     * Execute the console command.
     */
    public function handle(TemplateService $service)
    {
        $teamId = $this->argument('team_id');

        $query = Team::query();
        if ($teamId) {
            $query->where('id', $teamId);
        }

        $teams = $query->get();

        $this->info("Starting template sync for {$teams->count()} team(s)...");

        foreach ($teams as $team) {
            $this->info("Syncing Team: {$team->name} (ID: {$team->id})");

            try {
                if (!$team->whatsapp_business_account_id || !$team->whatsapp_access_token) {
                    $this->warn("Skipping Team {$team->id}: Missing WABA ID or Access Token.");
                    continue;
                }

                $count = $service->syncTemplates($team);
                $this->info("Synced {$count} templates.");

            } catch (\Exception $e) {
                $this->error("Failed to sync Team {$team->id}: " . $e->getMessage());
            }
        }

        $this->info('Template sync completed.');
    }
}
