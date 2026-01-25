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
];
