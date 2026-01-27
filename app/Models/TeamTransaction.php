<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];
}
