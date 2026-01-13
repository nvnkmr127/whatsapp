<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    /** @use HasFactory<\Database\Factories\FlowFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function sessions()
    {
        return $this->hasMany(FlowSession::class);
    }
}
