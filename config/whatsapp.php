<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Meta Graph API Version
    |--------------------------------------------------------------------------
    |
    | The current version of the Meta Graph API used for WhatsApp Business.
    |
    | Meta usually supports versions for 2+ years. v21.0 was current in 2024.
    |
    */
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    'trial_days' => env('WHATSAPP_TRIAL_DAYS', 14),

    'flow_private_key_path' => env('WHATSAPP_FLOW_PRIVATE_KEY_PATH', storage_path('app/flow_private_key.pem')),

    /*
    |--------------------------------------------------------------------------
    | Facebook App Credentials
    |--------------------------------------------------------------------------
    |
    | WHATSAPP_APP_ID should be the App ID of the Meta App that is configured
    | for WhatsApp Business API (Cloud API). This may differ from FACEBOOK_APP_ID.
    |
    */
    'app_id' => env('WHATSAPP_APP_ID', env('FACEBOOK_APP_ID')),
    'app_secret' => env('WHATSAPP_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),

    /*
    |--------------------------------------------------------------------------
    | System Identifiers (Global Defaults)
    |--------------------------------------------------------------------------
    | These identify the System User and primary Phone Number ID owned by the platform.
    |
    */
    'system_phone_number_id' => env('WHATSAPP_SYSTEM_PHONE_NUMBER_ID'),
    'system_waba_id' => env('WHATSAPP_SYSTEM_WABA_ID'),
    'system_access_token' => env('WHATSAPP_SYSTEM_ACCESS_TOKEN'),

    // Fallback access token + optional ffmpeg/ffprobe path overrides.
    // Read via config() so they survive config:cache (env() returns null then).
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'ffmpeg_path' => env('FFMPEG_PATH'),
    'ffprobe_path' => env('FFPROBE_PATH'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Calling Configuration
    |--------------------------------------------------------------------------
    */
    'calling' => [
        // Price per minute for calls (in USD)
        'price_per_minute' => env('WHATSAPP_CALL_PRICE_PER_MINUTE', 0.005),

        // Enable/disable calling globally
        'enabled' => env('WHATSAPP_CALLING_ENABLED', false),

        // Minimum wallet balance required to initiate or answer a call
        'min_balance_to_start' => env('WHATSAPP_CALL_MIN_BALANCE', 1.00),

        // Default ring timeout
        'ring_timeout_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Message Pricing (USD)
    |--------------------------------------------------------------------------
    | Pre-calculated pricing for different conversation categories.
    |
    | Note: These are fallback values; real-time pricing may differ.
    */
    'pricing' => [
        'marketing' => 0.10,
        'utility' => 0.05,
        'authentication' => 0.03,
        'service' => 0.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reliability Thresholds & Magic Numbers
    |--------------------------------------------------------------------------
    | Centralized control for system "Kill-switches" and warnings.
    */
    'thresholds' => [
        'health_quality_score' => 35, // Below this score, accounts are restricted.
        'template_readiness_score' => 70, // Below this score, template sending is blocked.
        'token_expiry_days' => 7, // Warn when tokens expire within this window.
        'webhook_deduplication_hours' => 24, // Window for ignoring duplicate messages.
    ],

    /*
    |--------------------------------------------------------------------------
    | UI & Interaction Constraints
    |--------------------------------------------------------------------------
    */
    'constraints' => [
        'button_title_max_length' => 20,
        'flow_message_version' => env('WHATSAPP_FLOW_VERSION', '3'),
        'default_flow_screen' => 'screen_welcome',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', env('WHATSAPP_VERIFY_TOKEN', 'whatsapp_webhook_token')),
];
