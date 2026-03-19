<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Broadcast Campaign Safety Limits
    |--------------------------------------------------------------------------
    | These limits are designed to prevent accidental bulk messaging 
    | that could lead to account bans or excessive costs.
    |
    */
    'max_recipients' => env('BROADCAST_MAX_RECIPIENTS', 100000),
    'hourly_limit' => env('BROADCAST_HOURLY_LIMIT', 10000),

    /*
    |--------------------------------------------------------------------------
    | Performance & Cache Settings
    |--------------------------------------------------------------------------
    */
    'usage_cache_ttl' => env('BROADCAST_USAGE_CACHE_TTL', 300), // Seconds
];
