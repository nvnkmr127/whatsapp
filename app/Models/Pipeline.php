<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'is_default',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];



    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function openDeals(): HasMany
    {
        return $this->hasMany(Deal::class)->where('status', 'open');
    }

    public function getTotalValueAttribute(): float
    {
        return $this->openDeals()->sum('value');
    }

    public function getWeightedValueAttribute(): float
    {
        return $this->openDeals()->sum('weighted_value');
    }

    public function getConversionRateAttribute(): float
    {
        $total = $this->deals()->count();
        if ($total === 0) {
            return 0;
        }
        $won = $this->deals()->where('status', 'won')->count();
        return round(($won / $total) * 100, 2);
    }

    public function getAverageDealSizeAttribute(): float
    {
        return $this->deals()->where('status', 'won')->avg('value') ?? 0;
    }

    public function getAverageSalesCycleAttribute(): int
    {
        return $this->deals()->where('status', 'won')->avg('days_in_pipeline') ?? 0;
    }
}
