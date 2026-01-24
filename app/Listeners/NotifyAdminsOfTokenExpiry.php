<?php

namespace App\Listeners;

use App\Events\WhatsAppTokenExpiringSoon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminsOfTokenExpiry implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(WhatsAppTokenExpiringSoon $event): void
    {
        $team = $event->team;

        // In a real implementation, we would fetch admins and send notifications.
        // For now, we log a critical alert which can be picked up by monitoring tools.

        Log::critical("WHATSAPP TOKEN EXPIRING SOON", [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'expires_at' => $team->whatsapp_token_expires_at,
            'days_remaining' => $team->whatsapp_token_expires_at ? $team->whatsapp_token_expires_at->diffInDays() : 'Unknown'
        ]);

        // Future: Notification::send($team->admins, new WhatsAppTokenExpiryNotification($team));
    }
}
