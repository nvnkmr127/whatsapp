<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamAddOn extends Model
{
    protected $table = 'team_addons';

    protected $fillable = [
        'team_id',
        'type',
        'settings',
        'expires_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'expires_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Check if the add-on is currently active (not expired)
     */
    public function isActive(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}
