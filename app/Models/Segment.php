<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rules' => 'array',
        'last_computed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the team that owns the segment.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get contacts in this segment (via materialized view).
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'segment_memberships')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    /**
     * Check if segment needs recomputation.
     */
    public function needsRecomputation(): bool
    {
        if (!$this->last_computed_at) {
            return true;
        }

        // Recompute if older than 1 hour for large segments
        if ($this->member_count > 100000) {
            return $this->last_computed_at->lt(now()->subHour());
        }

        return false;
    }

    /**
     * Check if this is a large segment.
     */
    public function isLargeSegment(): bool
    {
        return $this->member_count > 100000;
    }

    /**
     * Check if this is a medium segment.
     */
    public function isMediumSegment(): bool
    {
        return $this->member_count >= 10000 && $this->member_count <= 100000;
    }

    /**
     * Check if this is a small segment.
     */
    public function isSmallSegment(): bool
    {
        return $this->member_count < 10000;
    }
}
