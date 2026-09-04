<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automation extends Model
{
    use HasTeam;
    use SoftDeletes;

    protected $fillable = [
        'team_id', 'name', 'is_active', 'trigger_type', 'trigger_config',
        'flow_data', 'publish_log', 'description', 'version', 'last_published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_config' => 'array',
        'flow_data' => 'array',
        'publish_log' => 'array',
        'last_published_at' => 'datetime',
    ];

    // The trigger/execution paths always read the team (entitlement, health,
    // business-hours, settings), and $run->automation->team is accessed all
    // over. Eager-load it so strict mode doesn't flag a lazy-load violation.
    protected $with = ['team'];

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
