<?php

return [
    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'daily_limit' => env('WHATSAPP_DAILY_LIMIT', 1000),

    'queue' => [
        'name'        => env('WHATSAPP_QUEUE', 'whatsapp-messages'),
        'connection'  => env('WHATSAPP_QUEUE_CONNECTION', 'redis'),
        'retry_after' => 180,
        'timeout'     => 60,
    ],

    'paths' => [
        'qrcodes' => storage_path('app/public/whatsapp/qrcodes'),
        'media'   => storage_path('app/public/whatsapp/media'),
    ],

    'logging' => [
        'channel'  => env('WHATSAPP_LOG_CHANNEL', 'whatsapp'),
        'detailed' => env('WHATSAPP_DETAILED_LOGGING', true),
    ],
];
