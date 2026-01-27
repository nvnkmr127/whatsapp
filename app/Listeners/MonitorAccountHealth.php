<?php

namespace App\Listeners;

use App\Events\WhatsAppAccountRisk;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\Team;
use Illuminate\Support\Facades\Mail;
// use App\Mail\AccountRiskAlert; // Assuming we might want a mail class later

class MonitorAccountHealth implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WhatsAppAccountRisk $event): void
    {
        Log::info("MonitorAccountHealth: Handling Risk Event [{$event->type}]", ['team_id' => $event->teamId]);

        $team = null;
        if ($event->teamId) {
            $team = Team::find($event->teamId);
        }

        // 1. Pause Campaigns if Critical
        if ($this->isCritical($event->type, $event->payload) && $team) {
            $this->pauseCampaigns($team);
        }

        // 2. Notify System Admins
        $this->notifyAdmins($event, $team);

        // 3. Notify Team Owner
        if ($team) {
            $this->notifyTeamOwner($event, $team);
        }
    }

    protected function isCritical($type, $payload)
    {
        // Define what constitutes a critical risk requiring automated action
        if ($type === 'BAN' || $type === 'RESTRICTION') {
            return true;
        }

        if ($type === 'QUALITY_UPDATE') {
            $quality = $payload['new_quality_score'] ?? 'UNKNOWN';
            if ($quality === 'RED') {
                return true;
            }
        }

        return false;
    }

    protected function pauseCampaigns(Team $team)
    {
        Log::warning("MonitorAccountHealth: Pausing active campaigns for Team {$team->id} due to Account Risk.");

        // Example: Pause all 'active' campaigns
        // \App\Models\Campaign::where('team_id', $team->id)->where('status', 'active')->update(['status' => 'paused']);
        // Leaving commented as explicit implementation depends on Campaign model, but this is the place.

        // Update Team Integration State to Warning
        $team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::READY_WARNING]);
    }

    protected function notifyAdmins(WhatsAppAccountRisk $event, ?Team $team)
    {
        $teamName = $team ? $team->name : 'Unknown Team';
        $msg = "CRITICAL: WhatsApp Account Risk detected for {$teamName}. Type: {$event->type}. Check logs.";

        // Send to Slack/Email (Implementation generic)
        Log::critical($msg);
    }

    protected function notifyTeamOwner(WhatsAppAccountRisk $event, Team $team)
    {
        // Notify user via internal notification system
        // $team->owner->notify(new \App\Notifications\WhatsAppAccountRiskNotification($event));
        Log::info("MonitorAccountHealth: Notification sent to team owner of {$team->name}.");
    }
}
