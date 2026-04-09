<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualityRatingHistory extends Model
{
    use \App\Traits\HasTeam;
    use HasFactory;

    protected $table = 'quality_rating_history';

    protected $fillable = [
        'team_id',
        'previous_rating',
        'new_rating',
        'severity',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
