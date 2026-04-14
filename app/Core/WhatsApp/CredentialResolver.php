<?php

namespace App\Core\WhatsApp;

use App\Models\Team;
use Illuminate\Support\Facades\Log;

class CredentialResolver
{
    /**
     * Resolve the best credentials for a team.
     * Prioritizes team-specific manual connection settings.
     */
    public function resolve(Team $team): array
    {
        // Priority 1: Team-level override/manual connection
        $token = $team->whatsapp_access_token;
        $phoneId = $team->whatsapp_phone_number_id;
        $wabaId = $team->whatsapp_business_account_id;

        $appId = $team->whatsapp_app_id
            ?: (function_exists('get_setting') ? get_setting('whatsapp_wm_fb_app_id') : null)
            ?: config('whatsapp.app_id')
            ?: config('services.facebook.client_id');

        $appSecret = $team->whatsapp_settings['manual_app_secret'] ?? null
            ?: (function_exists('get_setting') ? get_setting('whatsapp_wm_fb_app_secret') : null)
            ?: config('whatsapp.app_secret')
            ?: config('services.facebook.client_secret');

        $verifyToken = $team->whatsapp_verify_token
            ?: config('whatsapp.webhook_verify_token')
            ?: config('whatsapp.verify_token');

        // If team-level is missing, could fall back to global, but usually teams have their own.
        // The user recently added 'whatsapp_app_id' and 'whatsapp_verify_token' to teams table.

        if (! $token || ! $phoneId) {
            Log::warning("WhatsApp credentials incomplete for team: {$team->id}");
        }

        return [
            'token' => $token,
            'phone_number_id' => $phoneId,
            'waba_id' => $wabaId,
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'verify_token' => $verifyToken,
        ];
    }
}
