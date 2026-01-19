<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'components' => 'array',
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

        foreach ($components as $component) {
            if (isset($component['text'])) {
                $content[] = $component['text'];
            }
        }

        return implode("\n", $content);
    }
}
