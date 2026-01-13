<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Here you can configure your API settings including tokens, permissions,
    | and other related configurations.
    |
    */

    'enabled' => env('API_ENABLED', false),

    'token' => env('API_TOKEN', null),

    'token_generated_at' => null,

    'abilities' => [
        // contact abilities
        'contacts.create',
        'contacts.read',
        'contacts.update',
        'contacts.delete',

        // status abilities
        'statuses.create',
        'statuses.read',
        'statuses.update',
        'statuses.delete',

        // source abilities
        'sources.create',
        'sources.read',
        'sources.update',
        'sources.delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Here you can configure the rate limiting for your API endpoints.
    |
    */

    'rate_limiting' => [
        'enabled'       => env('API_RATE_LIMIT_ENABLED', true),
        'max_attempts'  => env('API_RATE_LIMIT_MAX', 60),
        'decay_minutes' => env('API_RATE_LIMIT_DECAY', 1),
    ],

];
