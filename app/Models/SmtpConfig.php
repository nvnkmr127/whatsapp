<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Crypt;

class SmtpConfig extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'priority',
        'use_case',
        'is_active',
        'health_status',
        'last_checked_at',
        'failure_count'
    ];

    protected $casts = [
        'use_case' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'priority' => 'integer',
        'failure_count' => 'integer',
        'password' => 'encrypted',
    ];
}
