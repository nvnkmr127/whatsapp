<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];



    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function getConversionRateAttribute(): float
    {
        $total = $this->contacts()->count();
        if ($total === 0) {
            return 0;
        }
        
        $converted = $this->contacts()->whereHas('deals', function ($q) {
            $q->where('status', 'won');
        })->count();
        
        return round(($converted / $total) * 100, 2);
    }
}
