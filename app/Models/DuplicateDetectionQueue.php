<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuplicateDetectionQueue extends Model
{
    protected $table = 'duplicate_detection_queue';

    protected $guarded = [];

    protected $casts = [
        'match_reasons' => 'array',
        'confidence_score' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function potentialDuplicate()
    {
        return $this->belongsTo(Contact::class, 'potential_duplicate_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending duplicates.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for high confidence duplicates.
     */
    public function scopeHighConfidence($query, float $threshold = 80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }
}
