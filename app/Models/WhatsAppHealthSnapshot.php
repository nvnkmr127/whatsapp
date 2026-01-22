<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppHealthSnapshot extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_health_snapshots';

    protected $fillable = [
        'team_id',
        'health_score',
        'health_status',
        'token_health_score',
        'phone_health_score',
        'quality_health_score',
        'messaging_health_score',
        'token_valid',
        'token_expires_at',
        'token_days_until_expiry',
        'phone_verified',
        'phone_status',
        'quality_rating',
        'quality_trend',
        'messaging_tier',
        'daily_limit',
        'current_usage',
        'usage_percent',
        'snapshot_at',
    ];

    protected $casts = [
        'token_valid' => 'boolean',
        'phone_verified' => 'boolean',
        'token_expires_at' => 'datetime',
        'snapshot_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
