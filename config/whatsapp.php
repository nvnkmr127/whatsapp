<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Meta Graph API Version
    |--------------------------------------------------------------------------
    |
    | The current version of the Meta Graph API used for WhatsApp Business.
    |
    */
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'base_url' => 'https://graph.facebook.com',

    'trial_days' => env('WHATSAPP_TRIAL_DAYS', 14),

    'flow_private_key_path' => env('WHATSAPP_FLOW_PRIVATE_KEY_PATH', storage_path('app/flow_private_key.pem')),

    /*
    |--------------------------------------------------------------------------
    | Facebook App Credentials
    |--------------------------------------------------------------------------
    */
    'app_id' => env('FACEBOOK_APP_ID'),
    'app_secret' => env('FACEBOOK_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Calling Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Business calling features including
    | pricing, limits, and webhook settings.
    |
    */
    'calling' => [
        // Price per minute for calls (in USD)
        'price_per_minute' => env('WHATSAPP_CALL_PRICE_PER_MINUTE', 0.005),

        // Default monthly call limit (in minutes) if not set per team
        'default_monthly_limit' => env('WHATSAPP_DEFAULT_CALL_LIMIT', null),

        // Enable/disable calling globally
        'enabled' => env('WHATSAPP_CALLING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'whatsapp_webhook_token'),
];

