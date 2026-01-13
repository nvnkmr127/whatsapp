<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }
}
