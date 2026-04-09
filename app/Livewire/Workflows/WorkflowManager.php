<?php

namespace App\Livewire\Workflows;

use App\Models\ContactTag;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Workflow;
use Livewire\Attributes\Computed;
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

    public $search = '';

    public $filterFolder = '';

    public $folder = '';

    public $tagsText = '';

    public $showImportModal = false;

    public $importJson = '';

    public function mount()
    {
        $this->loadWorkflows();
    }

    public function updatedSearch()
    {
        $this->loadWorkflows();
    }

    public function updatedFilterFolder()
    {
        $this->loadWorkflows();
    }

    public function loadWorkflows()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-workflows');

        $query = Workflow::where(function ($q) {
            $q->where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam?->id)
                ->orWhereNull('team_id');
        })
            ->withCount('actions')
            ->latest();

        if (trim($this->search)) {
            $query->where('name', 'like', '%'.trim($this->search).'%');
        }

        if (trim($this->filterFolder)) {
            $query->where('folder', $this->filterFolder);
        }

        $this->workflows = $query->get();
    }

    public function create()
    {
        $this->reset(['name', 'description', 'triggerType', 'triggerConfig', 'actions', 'activeWorkflowId', 'activeWorkflow', 'folder', 'tagsText']);
        $this->isActive = true;
        $this->actions = [];
        $this->showCreateModal = true;
    }

    public function edit($id)
    {
        $workflow = Workflow::with('actions')->find($id);

        if (! $workflow || $workflow->team_id !== \Illuminate\Support\Facades\Auth::user()->currentTeam?->id) {
            return;
        }

        $this->activeWorkflowId = $id;
        $this->activeWorkflow = $workflow;
        $this->name = $workflow->name;
        $this->description = $workflow->description;
        $this->isActive = $workflow->is_active;
        $this->triggerType = $workflow->trigger_type;
        $this->triggerConfig = $workflow->trigger_config ?? [];
        $this->folder = $workflow->folder;
        $this->tagsText = implode(', ', $workflow->tags ?? []);

        // Load Definition (New) or Convert Actions (Legacy)
        if (! empty($workflow->definition)) {
            $this->actions = $workflow->definition;
        } else {
            $this->actions = $workflow->actions->map(function ($action) {
                return [
                    'type' => 'action',
                    'action_type' => $action->action_type,
                    'config' => $action->action_config ?? [],
                    'delay' => $action->delay_minutes ?? 0,
                ];
            })->toArray();
        }

        $this->showCreateModal = true;
    }

    public function save()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-workflows');

        $this->validate([
            'name' => 'required|string|max:255',
            'triggerType' => 'required|string',
        ]);

        $teamId = \Illuminate\Support\Facades\Auth::user()->currentTeam?->id;

        $tagsArray = array_filter(array_map('trim', explode(',', $this->tagsText)));

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'trigger_type' => $this->triggerType,
            'trigger_config' => $this->triggerConfig,
            'definition' => $this->actions, // Save JSON Flow
            'folder' => trim($this->folder) ?: null,
            'tags' => $tagsArray,
        ];

        if ($this->triggerType === 'webhook_incoming' && empty($data['trigger_config']['webhook_url_id'])) {
            $data['trigger_config']['webhook_url_id'] = \Illuminate\Support\Str::uuid()->toString();
            $this->triggerConfig['webhook_url_id'] = $data['trigger_config']['webhook_url_id'];
        }

        if ($this->activeWorkflowId) {
            $workflow = Workflow::find($this->activeWorkflowId);
            $workflow->update($data);
        } else {
            $data['team_id'] = $teamId;
            $data['created_by'] = \Illuminate\Support\Facades\Auth::id();
            $workflow = Workflow::create($data);
        }

        $this->showCreateModal = false;
        $this->loadWorkflows();

        audit(
            $this->activeWorkflowId ? 'workflow.updated' : 'workflow.created',
            \Illuminate\Support\Facades\Auth::id(),
            $workflow->id,
            'workflow',
            ['name' => $workflow->name]
        );

        $this->dispatch('notify', type: 'success', message: 'Workflow saved successfully');
    }

    public function duplicate($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-workflows');

        $workflow = Workflow::find($id);

        if ($workflow && $workflow->team_id === \Illuminate\Support\Facades\Auth::user()->currentTeam?->id) {
            $newWorkflow = $workflow->replicate();
            $newWorkflow->name = $workflow->name.' (Copy)';
            $newWorkflow->is_active = false;
            $newWorkflow->execution_count = 0;
            $newWorkflow->last_executed_at = null;
            $newWorkflow->save();

            $this->loadWorkflows();
            $this->dispatch('notify', type: 'success', message: 'Workflow duplicated explicitly!');
        }
    }

    public function export($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-workflows');

        $workflow = Workflow::find($id);

        if ($workflow && $workflow->team_id === \Illuminate\Support\Facades\Auth::user()->currentTeam?->id) {
            $data = [
                'name' => $workflow->name,
                'description' => $workflow->description,
                'trigger_type' => $workflow->trigger_type,
                'trigger_config' => $workflow->trigger_config,
                'definition' => $workflow->definition,
                'tags' => $workflow->tags,
            ];

            return response()->streamDownload(function () use ($data) {
                echo json_encode($data, JSON_PRETTY_PRINT);
            }, \Illuminate\Support\Str::slug($workflow->name).'-export.json');
        }
    }

    public function openImportModal()
    {
        $this->importJson = '';
        $this->showImportModal = true;
    }

    public function processImport()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-workflows');

        try {
            $data = json_decode($this->importJson, true, 512, JSON_THROW_ON_ERROR);

            Workflow::create([
                'team_id' => \Illuminate\Support\Facades\Auth::user()->currentTeam?->id,
                'name' => ($data['name'] ?? 'Imported Workflow').' (Imported)',
                'description' => $data['description'] ?? '',
                'is_active' => false,
                'trigger_type' => $data['trigger_type'] ?? 'contact_created',
                'trigger_config' => $data['trigger_config'] ?? [],
                'definition' => $data['definition'] ?? [],
                'tags' => $data['tags'] ?? [],
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            $this->showImportModal = false;
            $this->loadWorkflows();
            $this->dispatch('notify', type: 'success', message: 'Workflow imported successfully!');
        } catch (\Exception $e) {
            $this->addError('importJson', 'Invalid JSON syntax structure provided. '.$e->getMessage());
        }
    }

    public function addAction()
    {
        $this->actions[] = [
            'type' => 'action',
            'action_type' => 'add_tag',
            'config' => [],
            'delay' => 0,
        ];
    }

    public function addCondition()
    {
        $this->actions[] = [
            'type' => 'condition',
            'config' => [
                'match_type' => 'all', // 'all' (AND), 'any' (OR)
                'conditions' => [
                    ['field' => '', 'operator' => '=', 'value' => ''],
                ],
            ],
            'true_nodes' => [],
            'false_nodes' => [],
        ];
    }

    public function addNestedAction($parentIndex, $branch)
    {
        // branch is 'true_nodes' or 'false_nodes'
        $this->actions[$parentIndex][$branch][] = [
            'type' => 'action',
            'action_type' => 'add_tag',
            'config' => [],
            'delay' => 0,
        ];
    }

    public function removeNestedAction($parentIndex, $branch, $childIndex)
    {
        unset($this->actions[$parentIndex][$branch][$childIndex]);
        $this->actions[$parentIndex][$branch] = array_values($this->actions[$parentIndex][$branch]);
    }

    public function addSubCondition($index)
    {
        $this->actions[$index]['config']['conditions'][] = ['field' => '', 'operator' => '=', 'value' => ''];
    }

    public function removeSubCondition($index, $subIndex)
    {
        unset($this->actions[$index]['config']['conditions'][$subIndex]);
        $this->actions[$index]['config']['conditions'] = array_values($this->actions[$index]['config']['conditions']);
    }

    public function removeAction($index)
    {
        unset($this->actions[$index]);
        $this->actions = array_values($this->actions);
    }

    public $showLogsModal = false;

    public $workflowLogs = [];

    public $activeLogWorkflowName = '';

    public function viewLogs($id)
    {
        $workflow = Workflow::find($id);
        if (! $workflow || $workflow->team_id !== \Illuminate\Support\Facades\Auth::user()->currentTeam?->id) {
            return;
        }

        $this->activeLogWorkflowName = $workflow->name;
        $this->workflowLogs = \App\Models\WorkflowLog::where('workflow_id', $id)
            ->with(['subject'])
            ->latest()
            ->take(50)
            ->get();

        $this->showLogsModal = true;
    }

    public function closeLogs()
    {
        $this->showLogsModal = false;
        $this->workflowLogs = [];
    }

    #[Computed]
    public function folders()
    {
        return Workflow::where(function ($q) {
            $q->where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam?->id)
                ->orWhereNull('team_id');
        })
            ->whereNotNull('folder')
            ->where('folder', '!=', '')
            ->pluck('folder')
            ->unique();
    }

    public function render()
    {
        $teamId = \Illuminate\Support\Facades\Auth::user()->currentTeam?->id;

        return view('livewire.workflows.workflow-manager', [
            'tags' => ContactTag::where('team_id', $teamId)->get(),
            'stages' => PipelineStage::whereHas('pipeline', function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            })->get(),
            'pipelines' => Pipeline::where('team_id', $teamId)->get(),
            'users' => \Illuminate\Support\Facades\Auth::user()->currentTeam->allUsers(),
        ]);
    }
}
