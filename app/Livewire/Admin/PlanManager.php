<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use Livewire\Component;

class PlanManager extends Component
{
    public $plans;
    public $editingPlan = null;
    public $showModal = false;

    // Form fields
    public $name;
    public $monthly_price;
    public $message_limit;
    public $agent_limit;
    public $features = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'monthly_price' => 'required|numeric|min:0',
        'message_limit' => 'required|integer|min:0',
        'agent_limit' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->loadPlans();
    }

    public function loadPlans()
    {
        $this->plans = Plan::orderBy('monthly_price')->get();
    }

    public function createPlan()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editPlan($planId)
    {
        $plan = Plan::findOrFail($planId);
        $this->editingPlan = $plan->id;
        $this->name = $plan->name;
        $this->monthly_price = $plan->monthly_price;
        $this->message_limit = $plan->message_limit;
        $this->agent_limit = $plan->agent_limit;
        $this->features = $plan->features ?? [];
        $this->showModal = true;
    }

    public function savePlan()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'monthly_price' => $this->monthly_price,
            'message_limit' => $this->message_limit,
            'agent_limit' => $this->agent_limit,
            'features' => $this->features,
        ];

        if ($this->editingPlan) {
            Plan::find($this->editingPlan)->update($data);
            session()->flash('message', 'Plan updated successfully!');
        } else {
            Plan::create($data);
            session()->flash('message', 'Plan created successfully!');
        }

        $this->closeModal();
        $this->loadPlans();
    }

    public function deletePlan($planId)
    {
        Plan::findOrFail($planId)->delete();
        session()->flash('message', 'Plan deleted successfully!');
        $this->loadPlans();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editingPlan = null;
        $this->name = '';
        $this->monthly_price = '';
        $this->message_limit = '';
        $this->agent_limit = '';
        $this->features = [
            'chat' => false,
            'contacts' => false,
            'templates' => false,
            'campaigns' => false,
            'automations' => false,
            'analytics' => false,
            'commerce' => false,
            'ai' => false,
            'api_access' => false,
            'webhooks' => false,
        ];
    }

    public function render()
    {
        return view('livewire.admin.plan-manager');
    }
}
