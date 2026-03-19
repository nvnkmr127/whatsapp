<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerEvent extends Model
{
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'contact_id',
        'event_type',
        'event_data',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
