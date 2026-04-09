<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'credentials',
        'status',
        'settings',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array', // Auto-encrypt credentials in DB
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
