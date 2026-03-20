<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Add this use statement
use App\Models\Team; // Add this use statement for the relationship

class AiUsageLog extends Model
{
    /** @use HasFactory<\Database\Factories\AiUsageLogFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'provider',
        'model',
        'type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'cost' => 'decimal:6',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
