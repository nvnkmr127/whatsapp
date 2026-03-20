<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappTemplate extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'components' => 'array',
        'validation_results' => 'array',
        'readiness_score' => 'integer',
        'is_paused' => 'boolean',
        'variable_config' => 'array',
        'total_sent' => 'integer',
        'total_read' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get a concatenated string of all template components for preview/search.
     */
    public function getContentAttribute()
    {
        $content = [];
        $components = $this->components ?? [];
        
        if (is_string($components)) {
            $components = json_decode($components, true) ?: [];
        }

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
}
