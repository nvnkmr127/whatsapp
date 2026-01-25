<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'alert_type',
        'severity',
        'is_active',
        'trigger_conditions',
        'template_slug',
        'throttle_seconds',
        'escalation_path',
    ];

    protected $casts = [
        'alert_type' => AlertType::class,
        'severity' => AlertSeverity::class,
        'is_active' => 'boolean',
        'trigger_conditions' => 'array',
        'escalation_path' => 'array',
    ];

    public function logs()
    {
        return $this->hasMany(AlertLog::class, 'rule_id');
    }

    public function isCritical(): bool
    {
        return in_array($this->severity, [AlertSeverity::CRITICAL, AlertSeverity::EMERGENCY]);
    }
}
