<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'event_type',
        'identifier',
        'provider',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    /**
     * Get the user associated with the audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventColorAttribute(): string
    {
        $eventType = $this->event_type ?? '';

        return match (true) {
            str_contains($eventType, 'Success') => '#22c55e',
            str_contains($eventType, 'Failure') => '#ef4444',
            str_contains($eventType, 'Abuse') => '#f43f5e',
            str_contains($eventType, 'Request') => '#6366f1',
            default => '#64748b',
        };
    }

    public function getEventLabelAttribute(): string
    {
        return str_replace('.', ' ', $this->event_type ?? '');
    }
}
