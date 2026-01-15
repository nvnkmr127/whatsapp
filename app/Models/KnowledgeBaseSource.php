<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseSource extends Model
{
    protected $fillable = [
        'team_id',
        'type',
        'name',
        'path',
        'content',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
