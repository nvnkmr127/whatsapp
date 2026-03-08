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
        }
    }
}
