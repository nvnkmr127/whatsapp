<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseGap extends Model
{
    protected $fillable = [
        'team_id',
        'query',
        'gap_type',
        'search_metadata',
        'ai_metadata',
        'status',
        'resolution_note',
    ];

    protected $casts = [
        'search_metadata' => 'array',
        'ai_metadata' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
