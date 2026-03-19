<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Delivery Settings
    |--------------------------------------------------------------------------
    */
    'ttl' => env('OTP_TTL', 300), // Seconds
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),
    'daily_request_limit_per_user' => env('OTP_DAILY_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | System Origin (Global Default)
    |--------------------------------------------------------------------------
    | Fallback phone number used for system OTPs if team-specific 
    | configuration is missing.
    */
    'phone_number_id' => env('WHATSAPP_SYSTEM_PHONE_NUMBER_ID'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Template Configuration
    |--------------------------------------------------------------------------
    */
    'whatsapp_template_name' => env('WHATSAPP_OTP_TEMPLATE_NAME', 'verification_code'),
];
