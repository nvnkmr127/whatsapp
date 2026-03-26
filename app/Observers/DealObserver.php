<?php

namespace App\Observers;

use App\Models\Deal;
use App\Services\WorkflowEngine;

class DealObserver
{
    protected $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('stage_id')) {
            $this->engine->trigger('deal_stage_changed', $deal, [
                'stage_id' => $deal->stage_id,
                'old_stage_id' => $deal->getOriginal('stage_id'),
            ]);

            // Brand New Automation Engine
            if ($deal->contact) {
                try {
                    app(\App\Services\AutomationService::class)->checkSpecialTriggers($deal->contact, 'deal_stage_changed');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Automation [deal_stage_changed] Trigger Failed: " . $e->getMessage());
                }
            }
        }
        if ($deal->wasChanged('status')) {
            $status = $deal->status;
            if (in_array($status, ['won', 'lost']) && $deal->contact) {
                 try {
                     app(\App\Services\AutomationService::class)->checkSpecialTriggers($deal->contact, 'deal_' . $status, [
                        'deal_id' => $deal->id,
                        'deal_title' => $deal->title,
                        'deal_value' => $deal->value
                     ]);
                 } catch (\Exception $e) {
                     \Illuminate\Support\Facades\Log::error("Automation [deal_$status] Trigger Failed: " . $e->getMessage());
                 }
            }
        }
    }
}
