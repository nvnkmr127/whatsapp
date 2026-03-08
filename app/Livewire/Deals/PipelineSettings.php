<?php

namespace App\Livewire\Deals;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Livewire\Component;

class PipelineSettings extends Component
{
    public $pipelineId;
    public $pipelineName;
    public $stages = [];
    public $newStageName = '';

    protected $listeners = ['pipelineUpdated' => 'refresh'];

    public function mount()
    {
        $this->pipelineId = Pipeline::where('team_id', auth()->user()->currentTeam->id)->first()->id;
        $this->loadStages();
    }

    public function loadStages()
    {
        $pipeline = Pipeline::find($this->pipelineId);
        $this->pipelineName = $pipeline->name;
        $this->stages = $pipeline->stages->toArray();
    }

    public function addStage()
    {
        $this->validate(['newStageName' => 'required|min:3']);

        PipelineStage::create([
            'pipeline_id' => $this->pipelineId,
            'name' => $this->newStageName,
            'position' => count($this->stages),
            'probability' => 50,
        ]);

        $this->newStageName = '';
        $this->loadStages();
    }

    public function updateStageOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            PipelineStage::where('id', $id)->update(['position' => $index]);
        }
        $this->loadStages();
    }

    public function deleteStage($stageId)
    {
        $stage = PipelineStage::find($stageId);
        
        if ($stage->deals()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete stage with deals');
            return;
        }

        $stage->delete();
        $this->loadStages();
    }

    public function render()
    {
        return view('livewire.deals.pipeline-settings');
    }
}
