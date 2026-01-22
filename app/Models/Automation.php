<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_config' => 'array',
        'flow_data' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(AutomationStep::class)->orderBy('order_index');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function validate()
    {
        return (new \App\Services\AutomationValidationService())->validate($this);
    }
}
