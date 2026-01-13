<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WhatsappSettings extends Settings
{
    public string $wm_fb_app_id;

    public string $wm_fb_app_secret;

    public string $wm_business_account_id;

    public string $wm_access_token;

    public string $is_webhook_connected;

    public string $is_whatsmark_connected;

    public string $wm_default_phone_number;

    public string $wm_default_phone_number_id;

    public string $wm_health_check_time;

    public string $wm_health_data;

    public string $wm_profile_picture_url;

    public string $api_version;

    public string $daily_limit;

    public string $queue;

    public string $paths;

    public string $logging;

    public string $webhook_verify_token;

    public static function group(): string
    {
        return 'whatsapp';
    }
}
