<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Crypt;

class WhatsAppConfigService
{
    /**
     * Get the WhatsApp configuration for a specific team.
     * Decrypts the access token automatically via Eloquent casting, 
     * but this service provides a central place for any other config logic (fallbacks, etc).
     */
    public function getForTeam(Team $team)
    {
        if (!$team->whatsapp_connected) {
            return null;
        }

        return [
            'app_id' => $team->whatsapp_app_id ?: config('whatsapp.app_id'), 
            'verify_token' => $team->whatsapp_verify_token ?: config('whatsapp.webhook_verify_token'),
            'phone_number_id' => $team->whatsapp_phone_number_id,
            'business_account_id' => $team->whatsapp_business_account_id,
            'access_token' => $team->whatsapp_access_token, // Auto-decrypted
        ];
    }

    /**
     * Validate if a team has valid credentials.
     */
    public function hasValidCredentials(Team $team): bool
    {
        if (empty($team->whatsapp_access_token) || empty($team->whatsapp_phone_number_id)) {
            return false;
        }

        // Check token expiration
        if ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->isPast()) {
            \Log::warning("WhatsApp token expired for team {$team->id}");
            return false;
        }

        // Warn if expiring soon
        $thresholdDays = config('whatsapp.thresholds.token_expiry_days', 7);
        if ($team->whatsapp_token_expires_at && $team->whatsapp_token_expires_at->diffInDays() < $thresholdDays) {
            \Log::info("WhatsApp token expiring soon for team {$team->id}", [
                'expires_at' => $team->whatsapp_token_expires_at,
                'days_remaining' => $team->whatsapp_token_expires_at->diffInDays()
            ]);

            // Trigger event for notification
            event(new \App\Events\WhatsAppTokenExpiringSoon($team));
        }

        return true;
    }

    /**
     * Check if token needs refresh
     */
    public function needsRefresh(Team $team): bool
    {
        if (!$team->whatsapp_token_expires_at) {
            return false;
        }

        $thresholdDays = config('whatsapp.thresholds.token_expiry_days', 7);
        return $team->whatsapp_token_expires_at->diffInDays() < $thresholdDays;
    }
}
