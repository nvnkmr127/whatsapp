<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'report_type',
        'frequency',
        'last_sent_at',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
    ];
}
