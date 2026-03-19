<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBackup extends Model
{
    use HasUuids;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'type',
        'filename',
        'path',
        'disk',
        'remote_account_id',
        'remote_file_id',
        'size',
        'checksum',
        'signature',
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


    /**
     * Model A: Tenant connects their own Drive.
     * Identified by having a remote_account_id (tenant-specific).
     */
    public function isModelA(): bool
    {
        return !empty($this->remote_account_id);
    }

    /**
     * Model B: Platform has central Drive.
     * Identified by having no remote_account_id AND being stored on a cloud disk.
     */
    public function isModelB(): bool
    {
        return empty($this->remote_account_id) && $this->disk !== 'local';
    }
}
