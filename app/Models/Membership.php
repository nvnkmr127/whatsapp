<?php

namespace App\Models;

use Laravel\Jetstream\Membership as JetstreamMembership;

class Membership extends JetstreamMembership
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_call_enabled' => 'boolean',
        'last_call_ended_at' => 'datetime',
    ];
}
