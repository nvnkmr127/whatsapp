<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
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
}
