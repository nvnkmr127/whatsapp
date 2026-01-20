<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBackup extends Model
{
    use HasUuids;

    protected $fillable = [
        'team_id',
        'type',
        'filename',
        'path',
        'disk',
        'size',
        'checksum',
        'status',
        'error_message',
        'pruned_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'pruned_at' => 'datetime',
    ];

    /**
     * Get the team that owns the backup.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
