<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'name',
        'color',
        'position',
        'probability',
        'is_closed_won',
        'is_closed_lost',
        'expected_days',
    ];

    protected $casts = [
        'position' => 'integer',
        'probability' => 'decimal:2',
        'is_closed_won' => 'boolean',
        'is_closed_lost' => 'boolean',
        'expected_days' => 'integer',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }

    public function openDeals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id')->where('status', 'open');
    }

    public function getTotalValueAttribute(): float
    {
        return $this->openDeals()->sum('value');
    }

    public function getDealsCountAttribute(): int
    {
        return $this->openDeals()->count();
    }

    public function getAverageDaysInStageAttribute(): int
    {
        return $this->openDeals()->avg('days_in_stage') ?? 0;
    }
}
