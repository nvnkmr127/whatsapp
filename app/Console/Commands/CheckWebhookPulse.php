<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\WebhookPayload;
use App\Notifications\WhatsAppHealthNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWebhookPulse extends Command
{
    protected $signature = 'whatsapp:check-pulse {--hours=6 : Window of inactivity to flag}';
    protected $description = 'Verify that connected WhatsApp accounts are still receiving webhooks';

    public function handle()
    {
        $hours = $this->option('hours');
        $this->info("Checking WhatsApp Webhook Pulse (Window: {$hours} hours)...");

        $teamsChecked = 0;
        $teamsSilent = 0;

        Team::where('whatsapp_connected', true)
            ->whereNotNull('whatsapp_access_token')
            ->chunk(100, function ($teams) use ($hours, &$teamsChecked, &$teamsSilent) {
                foreach ($teams as $team) {
                    $lastWebhook = WebhookPayload::where('team_id', $team->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$lastWebhook || $lastWebhook->created_at->diffInHours() >= $hours) {
                        $this->warn("Team {$team->id} ({$team->name}) has not received a webhook in {$hours}+ hours.");

                        // Notify owner
                        try {
                            $team->owner->notify(new WhatsAppHealthNotification(
                                $team,
                                'webhook_pulse',
                                "Your WhatsApp connection may be interrupted. We haven't received any events (messages or status updates) from Meta in the last {$hours} hours."
                            ));
                        } catch (\Exception $e) {
                            Log::error("Failed to notify pulse alert for team {$team->id}: " . $e->getMessage());
                        }

                        $teamsSilent++;
                    }
                    $teamsChecked++;
                }
            });

        $this->info("Checked {$teamsChecked} teams. Found {$teamsSilent} silent connections.");

        return Command::SUCCESS;
    }
}
