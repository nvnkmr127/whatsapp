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

        $appIdSource = null;
        if ($team->whatsapp_app_id) {
            $appIdSource = 'team.whatsapp_app_id';
        } elseif (function_exists('get_setting') && get_setting('whatsapp_wm_fb_app_id')) {
            $appIdSource = 'settings.whatsapp_wm_fb_app_id';
        } elseif (config('whatsapp.app_id')) {
            $appIdSource = 'config.whatsapp.app_id';
        } elseif (config('services.facebook.client_id')) {
            $appIdSource = 'config.services.facebook.client_id';
        }

        $appId = $team->whatsapp_app_id
            ?: (function_exists('get_setting') ? get_setting('whatsapp_wm_fb_app_id') : null)
            ?: config('whatsapp.app_id')
            ?: config('services.facebook.client_id');

        $appSecretSource = null;
        if (($team->whatsapp_settings['manual_app_secret'] ?? null)) {
            $appSecretSource = 'team.whatsapp_settings.manual_app_secret';
        } elseif (function_exists('get_setting') && get_setting('whatsapp_wm_fb_app_secret')) {
            $appSecretSource = 'settings.whatsapp_wm_fb_app_secret';
        } elseif (config('whatsapp.app_secret')) {
            $appSecretSource = 'config.whatsapp.app_secret';
        } elseif (config('services.facebook.client_secret')) {
            $appSecretSource = 'config.services.facebook.client_secret';
        }

        $appSecret = $team->whatsapp_settings['manual_app_secret'] ?? null
            ?: (function_exists('get_setting') ? get_setting('whatsapp_wm_fb_app_secret') : null)
            ?: config('whatsapp.app_secret')
            ?: config('services.facebook.client_secret');

        $verifyTokenSource = null;
        if ($team->whatsapp_verify_token) {
            $verifyTokenSource = 'team.whatsapp_verify_token';
        } elseif (config('whatsapp.webhook_verify_token')) {
            $verifyTokenSource = 'config.whatsapp.webhook_verify_token';
        } elseif (config('whatsapp.verify_token')) {
            $verifyTokenSource = 'config.whatsapp.verify_token';
        }

        $verifyToken = $team->whatsapp_verify_token
            ?: config('whatsapp.webhook_verify_token')
            ?: config('whatsapp.verify_token');

        // If team-level is missing, could fall back to global, but usually teams have their own.
        // The user recently added 'whatsapp_app_id' and 'whatsapp_verify_token' to teams table.

        if (! $token || ! $phoneId) {
            Log::warning("WhatsApp credentials incomplete for team: {$team->id}");
        }

        Log::debug('WhatsApp CredentialResolver: resolved credentials', [
            'team_id' => $team->id,
            'waba_id' => $wabaId,
            'phone_number_id' => $phoneId,
            'token_present' => (bool) $token,
            'app_id_present' => (bool) $appId,
            'app_id_source' => $appIdSource,
            'app_secret_present' => (bool) $appSecret,
            'app_secret_source' => $appSecretSource,
            'verify_token_present' => (bool) $verifyToken,
            'verify_token_source' => $verifyTokenSource,
        ]);

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
