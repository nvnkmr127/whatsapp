<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncSession extends Model
{
    protected $fillable = [
        'integration_id',
        'type',
        'status',
        'total_entities',
        'processed_entities',
        'failed_entities',
        'started_at',
        'completed_at',
        'error_summary',
        'metadata'
    ];

    protected $casts = [
        'error_summary' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }
}
