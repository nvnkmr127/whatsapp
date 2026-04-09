<?php

namespace App\Livewire\Deals;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Livewire\Attributes\On;
use Livewire\Component;

class DealManager extends Component
{
    public $pipelineId;

    public $searchTerm = '';

    public $filterOwner = '';

    public $filterStatus = 'open';

    public function mount()
    {
        $defaultPipeline = Pipeline::where('team_id', auth()->user()->currentTeam->id)
            ->where('is_default', true)
            ->first();

        if (! $defaultPipeline) {
            // Create default pipeline if none exists
            $defaultPipeline = $this->createDefaultPipeline();
        }

        $this->pipelineId = $defaultPipeline->id;
    }

    protected function createDefaultPipeline()
    {
        $pipeline = Pipeline::create([
            'team_id' => auth()->user()->currentTeam->id,
            'name' => 'Sales Pipeline',
            'is_default' => true,
        ]);

        $stages = [
            ['name' => 'Lead In', 'probability' => 10, 'position' => 0],
            ['name' => 'Contact Made', 'probability' => 30, 'position' => 1],
            ['name' => 'Proposal Sent', 'probability' => 60, 'position' => 2],
            ['name' => 'Negotiation', 'probability' => 80, 'position' => 3],
            ['name' => 'Closed Won', 'probability' => 100, 'position' => 4, 'is_closed_won' => true],
            ['name' => 'Closed Lost', 'probability' => 0, 'position' => 5, 'is_closed_lost' => true],
        ];

        foreach ($stages as $stage) {
            $pipeline->stages()->create($stage);
        }

        return $pipeline;
    }

    #[On('deal-updated')]
    public function render()
    {
        $pipeline = Pipeline::with(['stages' => function ($query) {
            $query->orderBy('position');
        }])->find($this->pipelineId);

        $stages = $pipeline->stages;

        // Load deals for each stage
        foreach ($stages as $stage) {
            $query = Deal::where('stage_id', $stage->id)
                ->with(['contact', 'owner']);

            if ($this->searchTerm) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->searchTerm.'%')
                        ->orWhereHas('contact', function ($c) {
                            $c->where('name', 'like', '%'.$this->searchTerm.'%');
                        });
                });
            }

            if ($this->filterOwner) {
                $query->where('owner_id', $this->filterOwner);
            }

            if ($this->filterStatus !== 'all') {
                if ($this->filterStatus === 'open') {
                    $query->where('status', 'open');
                } else {
                    $query->where('status', $this->filterStatus);
                }
            }

            $stage->deals = $query->orderByDesc('created_at')->get();
            $stage->total_value = $stage->deals->sum('value');
        }

        return view('livewire.deals.deal-manager', [
            'pipeline' => $pipeline,
            'stages' => $stages,
            'pipelines' => Pipeline::where('team_id', auth()->user()->currentTeam->id)->get(),
            'teamMembers' => auth()->user()->currentTeam->allUsers(),
        ]);
    }

    public function updateDealStage($dealId, $newStageId)
    {
        $deal = Deal::find($dealId);
        $stage = PipelineStage::find($newStageId);

        if ($deal && $stage && $deal->pipeline_id === $stage->pipeline_id) {
            $deal->moveTo($stage);
            $this->dispatch('notify',
                type: 'success',
                message: 'Deal moved to '.$stage->name
            );
        }
    }
}
