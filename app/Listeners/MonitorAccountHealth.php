<?php

namespace App\Listeners;

use App\Events\WhatsAppAccountRisk;
use App\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

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

        \App\Models\Campaign::where('team_id', $team->id)
            ->where('status', 'active')
            ->update(['status' => 'paused']);

        $team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::READY_WARNING]);
    }

    protected function notifyAdmins(WhatsAppAccountRisk $event, ?Team $team)
    {
        $teamName = $team ? $team->name : 'Unknown Team';
        Log::critical("CRITICAL: WhatsApp Account Risk detected for {$teamName}. Type: {$event->type}. Check logs.");
    }

    protected function notifyTeamOwner(WhatsAppAccountRisk $event, Team $team)
    {
        $owner = \App\Models\User::find($team->user_id);
        if ($owner) {
            $owner->notify(new \App\Notifications\WhatsAppAccountRiskNotification($event));
        }
        Log::info("MonitorAccountHealth: Notifying owner of team {$team->name} about risk type {$event->type}.");
    }
}
