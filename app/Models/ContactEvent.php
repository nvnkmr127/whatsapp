<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
