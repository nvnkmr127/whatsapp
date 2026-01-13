<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Secret Key for Signing
    |--------------------------------------------------------------------------
    |
    | This key is used for signing webhook payloads to ensure they haven't been
    | tampered with during transmission.
    |
    */
    'signing_secret' => env('WEBHOOK_SIGNING_SECRET', '123'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Headers
    |--------------------------------------------------------------------------
    |
    | Default headers to be sent with webhook requests
    |
    */
    'headers' => [
        'User-Agent'   => 'whatsmark-Webhook/1.0',
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry attempts and timeout for failed webhook deliveries
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'timeout'      => 30,
    ],
];
