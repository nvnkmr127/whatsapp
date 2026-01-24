<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseSource extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'team_id',
        'type',
        'name',
        'path',
        'content',
        'metadata',
        'is_active',
        'status',
        'error_message',
        'last_synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
