<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationStepLedger extends Model
{
    protected $table = 'automation_step_ledger';
    protected $guarded = [];

    protected $casts = [
        'output_state' => 'array'
    ];

    public function run()
    {
        return $this->belongsTo(AutomationRun::class, 'automation_run_id');
    }
}
