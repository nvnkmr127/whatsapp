<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'team_id',
        'suppression_key',
        'status',
        'severity',
        'payload',
        'error_message',
        'triggered_at',
        'resolved_at',
    ];

    protected $casts = [
        'severity' => AlertSeverity::class,
        'payload' => 'array',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(AlertRule::class, 'rule_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
