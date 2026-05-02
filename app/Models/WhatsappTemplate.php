<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappTemplate extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'components' => 'array',
        'validation_results' => 'array',
        'readiness_score' => 'integer',
        'is_paused' => 'boolean',
        'is_paused_by_meta' => 'boolean',
        'is_mobile_draft' => 'boolean',
        'variable_config' => 'array',
        'total_sent' => 'integer',
        'total_read' => 'integer',
        'last_synced_at' => 'datetime',
        'last_status_sync_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Ensure components is ALWAYS an array even if stored as a string or null.
     */
    public function getComponentsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        // If it's already an array (due to casting)
        if (is_array($value)) {
            return $value;
        }

        // If it's a JSON string, decode it
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get a concatenated string of all template components for preview/search.
     */
    public function getContentAttribute()
    {
        $content = [];
        $components = $this->components;

        if (is_iterable($components)) {
            foreach ($components as $component) {
                if (isset($component['text'])) {
                    $content[] = $component['text'];
                }
            }
        }

        return implode("\n", $content);
    }

    /**
     * Scope a query to only include templates that are safe for sending.
     * Use with readiness check in application logic for stricter control.
     */
    public function scopeSafeForSending($query)
    {
        return $query->where('status', 'APPROVED')
            ->where('is_paused', false)
            ->where('readiness_score', '>=', 70);
    }

    /**
     * Scope a query to only include mobile drafts.
     */
    public function scopeDrafts($query)
    {
        return $query->where('is_mobile_draft', true);
    }

    /**
     * Scope a query to only include live (non-draft) templates.
     */
    public function scopeLive($query)
    {
        return $query->where('is_mobile_draft', false);
    }
}
