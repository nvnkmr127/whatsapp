<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'definition',
        'is_active',
        'trigger_type',
        'trigger_config',
        'execution_count',
        'success_count',
        'failure_count',
        'conversions_count',
        'time_saved_minutes',
        'last_executed_at',
        'created_by',
        'folder',
        'tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_config' => 'encrypted:array',
        'definition' => 'encrypted:array',
        'tags' => 'array',
        'last_executed_at' => 'datetime',
        'execution_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'conversions_count' => 'integer',
        'time_saved_minutes' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class)->orderByDesc('created_at');
    }
}
