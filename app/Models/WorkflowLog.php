<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowLog extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'workflow_id',
        'team_id',
        'subject_type',
        'subject_id',
        'status',
        'error_message',
        'execution_data',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'execution_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }



    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
