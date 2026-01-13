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
}
