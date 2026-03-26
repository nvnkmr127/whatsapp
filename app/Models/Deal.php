<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use HasFactory, SoftDeletes;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'contact_id',
        'pipeline_id',
        'stage_id',
        'owner_id',
        'title',
        'description',
        'value',
        'currency',
        'expected_close_date',
        'actual_close_date',
        'status',
        'lost_reason',
        'notes',
        'probability',
        'weighted_value',
        'custom_fields',
        'source',
        'last_activity_at',
        'days_in_stage',
        'days_in_pipeline',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'decimal:2',
        'weighted_value' => 'decimal:2',
        'custom_fields' => 'array',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'last_activity_at' => 'datetime',
        'days_in_stage' => 'integer',
        'days_in_pipeline' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($deal) {
            $deal->last_activity_at = now();
            $deal->updateWeightedValue();
        });

        static::updating(function ($deal) {
            if ($deal->isDirty('value') || $deal->isDirty('probability')) {
                $deal->updateWeightedValue();
            }

            if ($deal->isDirty('stage_id')) {
                $deal->logStageChange();
                $deal->days_in_stage = 0;
            }

            if ($deal->isDirty('status')) {
                $deal->logStatusChange();

                if (in_array($deal->status, ['won', 'lost'])) {
                    $deal->actual_close_date = now();
                }
            }

            $deal->last_activity_at = now();
        });

        static::created(function ($deal) {
            try {
                if (class_exists(\App\Services\WorkflowEngine::class)) {
                    app(\App\Services\WorkflowEngine::class)->trigger('deal_created', $deal, ['source' => 'system']);
                }
                
                // Advanced Automation Engine
                if (class_exists(\App\Services\AutomationService::class)) {
                    app(\App\Services\AutomationService::class)->checkDealTriggers($deal, 'deal_created');
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Deal Created Trigger Failed: ' . $e->getMessage());
            }
        });

        static::updated(function ($deal) {
            try {
                $hasAutomationEngine = class_exists(\App\Services\AutomationService::class);
                $automationService = $hasAutomationEngine ? app(\App\Services\AutomationService::class) : null;

                if (class_exists(\App\Services\WorkflowEngine::class)) {
                    if ($deal->wasChanged('stage_id')) {
                        app(\App\Services\WorkflowEngine::class)->trigger('deal_stage_changed', $deal, [
                            'old_stage' => $deal->getOriginal('stage_id'),
                            'new_stage' => $deal->stage_id
                        ]);
                        
                        if ($automationService) {
                            $automationService->checkDealTriggers($deal, 'deal_stage_changed', [
                                'old_stage_id' => $deal->getOriginal('stage_id'),
                                'new_stage_id' => $deal->stage_id
                            ]);
                        }
                    }
                    
                    if ($deal->wasChanged('status') && $deal->status === 'won') {
                        app(\App\Services\WorkflowEngine::class)->trigger('deal_won', $deal, ['value' => $deal->value]);
                        
                        if ($automationService) {
                            $automationService->checkDealTriggers($deal, 'deal_won', ['value' => $deal->value]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Deal Updated Trigger Failed: ' . $e->getMessage());
            }
        });
    }



    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DealActivity::class)->orderByDesc('occurred_at');
    }

    public function products(): HasMany
    {
        return $this->hasMany(DealProduct::class);
    }

    public function updateWeightedValue(): void
    {
        $this->weighted_value = ($this->value * $this->probability) / 100;
    }

    public function moveTo(PipelineStage $stage): void
    {
        $this->stage_id = $stage->id;
        $this->probability = $stage->probability;

        if ($stage->is_closed_won) {
            $this->status = 'won';
        } elseif ($stage->is_closed_lost) {
            $this->status = 'lost';
        }

        $this->save();
    }

    public function markAsWon(): void
    {
        $this->status = 'won';
        $this->actual_close_date = now();
        $this->save();
    }

    public function markAsLost(string $reason = null): void
    {
        $this->status = 'lost';
        $this->lost_reason = $reason;
        $this->actual_close_date = now();
        $this->save();
    }

    public function addNote(string $content, User $user = null): DealActivity
    {
        return $this->activities()->create([
            'type' => 'note',
            'content' => $content,
            'user_id' => $user?->id ?? auth()->id(),
            'occurred_at' => now(),
        ]);
    }

    protected function logStageChange(): void
    {
        if ($this->exists) {
            $oldStage = PipelineStage::find($this->getOriginal('stage_id'));
            $newStage = PipelineStage::find($this->stage_id);

            $this->activities()->create([
                'type' => 'stage_change',
                'content' => "Stage changed from {$oldStage->name} to {$newStage->name}",
                'user_id' => auth()->id(),
                'occurred_at' => now(),
                'metadata' => [
                    'old_stage_id' => $oldStage->id,
                    'new_stage_id' => $newStage->id,
                ],
            ]);
        }
    }

    protected function logStatusChange(): void
    {
        if ($this->exists) {
            $oldStatus = $this->getOriginal('status');

            $this->activities()->create([
                'type' => 'status_change',
                'content' => "Status changed from {$oldStatus} to {$this->status}",
                'user_id' => auth()->id(),
                'occurred_at' => now(),
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $this->status,
                ],
            ]);
        }
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->expected_close_date
            && $this->expected_close_date->isPast()
            && $this->status === 'open';
    }

    public function getIsAtRiskAttribute(): bool
    {
        if (!$this->stage->expected_days) {
            return false;
        }

        return $this->days_in_stage > $this->stage->expected_days;
    }
}
