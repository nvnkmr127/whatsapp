<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppHealthAlert extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_health_alerts';

    protected $fillable = [
        'team_id',
        'severity',
        'dimension',
        'alert_type',
        'message',
        'metadata',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'auto_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'acknowledged' => 'boolean',
        'auto_resolved' => 'boolean',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function acknowledge(User $user): void
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    public function resolve(bool $auto = false): void
    {
        $this->update([
            'auto_resolved' => $auto,
            'resolved_at' => now(),
        ]);
    }
}
