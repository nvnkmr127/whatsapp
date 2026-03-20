<?php

namespace App\Livewire\Automations\Traits;

use App\Models\Automation;
use App\Services\AutomationValidationService;
use Illuminate\Support\Facades\Auth;

trait HasValidation
{
    public function runValidation()
    {
        $automation = new Automation([
            'team_id' => Auth::user()->currentTeam->id,
            'trigger_type' => $this->triggerType,
            'trigger_config' => $this->triggerConfig,
            'flow_data' => [
                'nodes' => array_values($this->nodes),
                'edges' => array_values($this->edges)
            ]
        ]);

        $results = (new AutomationValidationService())->validate($automation);
        $this->validationIssues = $results['issues'];
        $this->isActivatable = $results['is_activatable'];

        $this->calculateStepMetadata();
    }
}
