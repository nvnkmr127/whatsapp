<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TenantBackup extends Model
{
    use \App\Traits\HasTeam;
    use HasUuids;

    protected $fillable = [
        'team_id',
        'type',
        'name',
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
     * Model B: Platform has central Drive.
     * Identified by having no remote_account_id AND being stored on a cloud disk.
     */
    public function isModelB(): bool
    {
        return empty($this->remote_account_id) && $this->disk !== 'local';
    }
}
