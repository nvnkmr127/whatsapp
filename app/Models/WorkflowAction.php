<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'action_type',
        'action_config',
        'order',
        'delay_minutes',
    ];

    protected $casts = [
        'action_config' => 'array',
        'order' => 'integer',
        'delay_minutes' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
