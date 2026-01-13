<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'state_data' => 'array',
        'resume_at' => 'datetime'
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
