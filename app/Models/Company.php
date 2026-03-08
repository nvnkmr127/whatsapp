<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'domain',
        'industry',
        'size',
        'website',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'description',
        'logo_url',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function getTotalValueAttribute(): float
    {
        return $this->deals()->sum('value');
    }

    public function getOpenDealsValueAttribute(): float
    {
        return $this->deals()->where('status', 'open')->sum('value');
    }
}
