<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $fillable = [
        'team_id', 'name', 'first_response_minutes',
        'resolution_minutes', 'is_default', 'business_hours',
    ];

    protected $casts = [
        'is_default'       => 'boolean',
        'business_hours'   => 'array',
    ];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }

    public function getFirstResponseLabelAttribute(): string
    {
        if ($this->first_response_minutes === 0) return 'No limit';
        if ($this->first_response_minutes < 60) return "{$this->first_response_minutes}m";
        $h = intdiv($this->first_response_minutes, 60);
        $m = $this->first_response_minutes % 60;
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    public function getResolutionLabelAttribute(): string
    {
        if ($this->resolution_minutes === 0) return 'No limit';
        $h = intdiv($this->resolution_minutes, 60);
        $m = $this->resolution_minutes % 60;
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }
}
