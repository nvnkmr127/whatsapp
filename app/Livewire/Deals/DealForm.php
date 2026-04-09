<?php

namespace App\Livewire\Deals;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Livewire\Component;

class DealForm extends Component
{
    public $dealId;

    public $pipelineId;

    public $stageId;

    public $contactId;

    public $ownerId;

    public $title;

    public $value = 0;

    public $currency = 'USD';

    public $probability;

    public $expectedCloseDate;

    public $description;

    public $isOpen = false;

    protected $listeners = ['openDealForm'];

    public function openDealForm($dealId = null, $pipelineId = null, $stageId = null)
    {
        $this->reset();
        $this->isOpen = true;

        if ($dealId) {
            $deal = Deal::find($dealId);
            $this->dealId = $deal->id;
            $this->pipelineId = $deal->pipeline_id;
            $this->stageId = $deal->stage_id;
            $this->contactId = $deal->contact_id;
            $this->ownerId = $deal->owner_id;
            $this->title = $deal->title;
            $this->value = $deal->value;
            $this->currency = $deal->currency;
            $this->probability = $deal->probability;
            $this->expectedCloseDate = $deal->expected_close_date?->format('Y-m-d');
            $this->description = $deal->description;
        } else {
            $this->pipelineId = $pipelineId;
            $this->stageId = $stageId;
            $this->ownerId = auth()->id();

            if ($stageId) {
                $stage = PipelineStage::find($stageId);
                $this->probability = $stage->probability;
            }
        }
    }

    public function updatedStageId($value)
    {
        if ($value) {
            $stage = PipelineStage::find($value);
            $this->probability = $stage->probability;
        }
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|min:3',
            'contactId' => 'required|exists:contacts,id',
            'pipelineId' => 'required|exists:pipelines,id',
            'stageId' => 'required|exists:pipeline_stages,id',
            'value' => 'required|numeric|min:0',
            'probability' => 'required|numeric|min:0|max:100',
        ]);

        $data = [
            'team_id' => auth()->user()->currentTeam->id,
            'contact_id' => $this->contactId,
            'pipeline_id' => $this->pipelineId,
            'stage_id' => $this->stageId,
            'owner_id' => $this->ownerId,
            'title' => $this->title,
            'value' => $this->value,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'expected_close_date' => $this->expectedCloseDate,
            'description' => $this->description,
        ];

        if ($this->dealId) {
            $deal = Deal::find($this->dealId);
            $deal->update($data);
            $message = 'Deal updated successfully';
        } else {
            Deal::create($data);
            $message = 'Deal created successfully';
        }

        $this->isOpen = false;
        $this->dispatch('deal-updated');
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function render()
    {
        return view('livewire.deals.deal-form', [
            'pipelines' => Pipeline::where('team_id', auth()->user()->currentTeam->id)->get(),
            'stages' => $this->pipelineId ? PipelineStage::where('pipeline_id', $this->pipelineId)->orderBy('position')->get() : [],
            'contacts' => Contact::where('team_id', auth()->user()->currentTeam->id)->orderBy('name')->limit(50)->get(),
            'teamMembers' => auth()->user()->currentTeam->allUsers(),
        ]);
    }
}
