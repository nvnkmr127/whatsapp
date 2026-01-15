<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'monthly_price',
        'message_limit',
        'agent_limit',
        'features',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'message_limit' => 'integer',
        'agent_limit' => 'integer',
        'features' => 'array',
    ];

    /**
     * Check if plan has a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features;

        // Handle case where features might be a JSON string
        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        if (!is_array($features)) {
            return false;
        }

        return $features[$feature] ?? false;
    }

    /**
     * Get all enabled features
     */
    public function getEnabledFeatures(): array
    {
        $features = $this->features;

        // Handle case where features might be a JSON string
        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        if (!is_array($features)) {
            return [];
        }

        return array_keys(array_filter($features));
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format((float) $this->monthly_price, 2);
    }
}
