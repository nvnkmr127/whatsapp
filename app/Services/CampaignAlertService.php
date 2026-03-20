<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CampaignFailedNotification;

class CampaignAlertService
{
    /**
     * Notify about a campaign failure if we haven't notified recently.
     */
    public function notifyFailure(Campaign $campaign, string $errorMessage)
    {
        // Avoid spamming: Only notify once per hour per campaign
        if ($campaign->error_notified_at && $campaign->error_notified_at->diffInMinutes(now()) < 60) {
            return;
        }

        try {
            $campaign->update([
                'error_message' => $errorMessage,
                'error_notified_at' => now(),
            ]);

            $team = Team::find($campaign->team_id);
            if ($team && $team->owner) {
                $team->owner->notify(new \App\Notifications\CampaignFailedNotification($campaign, $errorMessage));
                Log::info("Sent failure alert for Campaign {$campaign->id} to Team {$team->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send campaign alert: " . $e->getMessage());
        }
    }
}
