<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    use HasTeam;

    protected $fillable = [
        'team_id', 'name', 'is_active', 'trigger_type', 'trigger_config',
        'flow_data', 'publish_log', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_config' => 'array',
        'flow_data' => 'array',
        'publish_log' => 'array',
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
        return (new \App\Services\AutomationValidationService)->validate($this);
    }

    public function runs()
    {
        return $this->hasMany(AutomationRun::class);
    }
}
