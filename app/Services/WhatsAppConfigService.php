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
            'app_id' => config('services.whatsapp.app_id'), // Global env
            'verify_token' => config('services.whatsapp.verify_token'), // Global env

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
        return !empty($team->whatsapp_access_token) && !empty($team->whatsapp_phone_number_id);
    }
}
