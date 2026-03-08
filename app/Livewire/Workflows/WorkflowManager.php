<?php

namespace App\Livewire\Workflows;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use Livewire\Component;

class WorkflowManager extends Component
{
    public $workflows = [];
    public $activeWorkflowId;
    public $activeWorkflow;

    public $name = 'New Workflow';
    public $description;
    public $isActive = true;
    public $triggerType = 'contact_created';
    public $triggerConfig = [];

    public $actions = [];

    public $showCreateModal = false;

    public function mount()
    {
        $this->loadWorkflows();
    }

    public function loadWorkflows()
    {
        $this->workflows = Workflow::where('team_id', auth()->user()->currentTeam->id)
            ->latest()
            ->get();
    }

    public function create()
    {
        $this->reset(['name', 'description', 'triggerType', 'triggerConfig', 'actions', 'activeWorkflowId', 'activeWorkflow']);
        $this->isActive = true;
        $this->actions = [];
        $this->showCreateModal = true;
    }

    public function edit($id)
    {
        $workflow = Workflow::with('actions')->find($id);
        
        if (!$workflow || $workflow->team_id !== auth()->user()->currentTeam->id) {
            return;
        }

        $this->activeWorkflowId = $id;
        $this->activeWorkflow = $workflow;
        $this->name = $workflow->name;
        $this->description = $workflow->description;
        $this->isActive = $workflow->is_active;
        $this->triggerType = $workflow->trigger_type;
        $this->triggerConfig = $workflow->trigger_config ?? [];
        $this->actions = $workflow->actions->map(function ($action) {
            return [
                'type' => $action->action_type,
                'config' => $action->action_config ?? [],
                'delay' => $action->delay_minutes ?? 0,
            ];
        })->toArray();

        $this->showCreateModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'triggerType' => 'required|string',
        ]);

        $teamId = auth()->user()->currentTeam->id;

        if ($this->activeWorkflowId) {
            $workflow = Workflow::find($this->activeWorkflowId);
            $workflow->update([
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->isActive,
                'trigger_type' => $this->triggerType,
                'trigger_config' => $this->triggerConfig,
            ]);
        } else {
            $workflow = Workflow::create([
                'team_id' => $teamId,
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->isActive,
                'trigger_type' => $this->triggerType,
                'trigger_config' => $this->triggerConfig,
                'created_by' => auth()->id(),
            ]);
        }

        // Sync Actions
        $workflow->actions()->delete();

        foreach ($this->actions as $index => $actionData) {
            $workflow->actions()->create([
                'action_type' => $actionData['type'],
                'action_config' => $actionData['config'] ?? [],
                'order' => $index,
                'delay_minutes' => $actionData['delay'] ?? 0,
            ]);
        }

        $this->showCreateModal = false;
        $this->loadWorkflows();
        $this->dispatch('notify', type: 'success', message: 'Workflow saved successfully');
    }

    public function addAction()
    {
        $this->actions[] = [
            'type' => 'add_tag',
            'config' => [],
            'delay' => 0,
        ];
    }

    public function removeAction($index)
    {
        unset($this->actions[$index]);
        $this->actions = array_values($this->actions);
    }

    public function render()
    {
        $teamId = auth()->user()->currentTeam->id;

        return view('livewire.workflows.workflow-manager', [
            'tags' => ContactTag::where('team_id', $teamId)->get(),
            'stages' => PipelineStage::whereHas('pipeline', function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            })->get(),
            'pipelines' => Pipeline::where('team_id', $teamId)->get(),
            'users' => auth()->user()->currentTeam->allUsers(),
        ]);
    }
}
