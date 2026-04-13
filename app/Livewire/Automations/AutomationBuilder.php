<?php

namespace App\Livewire\Automations;

use App\Livewire\Automations\Traits\HasHistory;
use App\Livewire\Automations\Traits\HasNodeEditing;
use App\Livewire\Automations\Traits\HasNodes;
use App\Livewire\Automations\Traits\HasPersistence;
use App\Livewire\Automations\Traits\HasTemplates;
use App\Livewire\Automations\Traits\HasTestMode;
use App\Livewire\Automations\Traits\HasValidation;
use App\Models\Automation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

use App\Traits\HasFlashMessages;

class AutomationBuilder extends Component
{
    use HasFlashMessages, HasHistory, HasNodeEditing, HasNodes, HasPersistence, HasTemplates, HasTestMode, HasValidation, WithFileUploads;

    public $automationId;

    protected $listeners = [
        'template-selected' => 'loadTemplate'
    ];

    public $name;

    // Trigger Properties
    public $triggerType = 'keyword';

    public $triggerConfig = [
        'keywords' => [],
        'is_regex' => false,
        'add_tags' => [],
        'remove_tags' => [],
        'webhook_url' => null,
        'template_name' => null,
        'button_text' => null,
        'source_id' => null,
        'headline' => null,
        'source_url' => null,
    ];

    public $triggerKeywordsString = '';

    public $nodes = [];

    public $edges = [];

    public $stepMetadata = [];

    public $selectedNodeId = null;

    public $selectedEdgeIndex = null;

    public $edgeCondition = '';

    // Node editing properties
    public $nodeLabel = '';

    public $nodeTag = '';

    public $nodeText = '';

    public $nodeButtonText = '';

    public $nodeUrl = '';

    public $nodeMethod = 'GET';

    public $nodeSaveTo = '';

    public $nodeHours = 0;

    public $nodeMinutes = 0;

    public $nodeOptions = [];

    public $newOption = '';

    // Carousel & Advanced properties
    public $nodeCards = [];

    public $nodeHeaders = [];

    public $nodeJson = '';

    public $nodeContacts = [];

    public $nodeDelayValue = 5;

    public $nodeDelayUnit = 'seconds';

    public $nodeRatio = 50;

    public $showErrorModal = false;

    public $nodeModel = 'gpt-4o';

    public $nodeUseKb = false;

    public $nodeKbSourceIds = [];

    public $nodeKbScope = 'all';

    public $nodeKbStrict = true;

    public $nodeLanguage = 'en';

    public $nodeOperator = 'eq';

    // Text Node specific
    public $nodeTyping = false;

    public $nodeDelaySeconds = 0;

    public $nodeDelayMinutes = 0;

    public $nodeDelayHours = 0;

    public $validationIssues = [];
    public $isActivatable = true;

    // Versioning & Publishing
    public $showPublishModal = false;

    public $publishNote = '';

    public $version = 1;

    public $lastPublishedAt = null;

    public $publishLog = [];

    public $isDirty = false;

    // CRM / Context / Debug
    public $nodeProvider = '';

    public $nodeAction = '';

    public $debugMode = false;

    public $debugLogs = [];

    public function nodeUseKb($value = null): void
    {
        if ($value === null) {
            $this->nodeUseKb = ! (bool) $this->nodeUseKb;
        } else {
            $this->nodeUseKb = (bool) $value;
        }

        if (! $this->nodeUseKb) {
            $this->nodeKbSourceIds = [];
            $this->nodeKbScope = 'all';
            $this->nodeKbStrict = true;
        }

        $this->updateNodeData();
        $this->runValidation();
    }

    public function updatedNodeUseKb($value): void
    {
        if ($value) {
            return;
        }

        $this->nodeKbSourceIds = [];
        $this->nodeKbScope = 'all';
        $this->nodeKbStrict = true;

        $this->updateNodeData();
        $this->runValidation();
    }

    #[Computed]
    public function availableTags() { return \App\Models\ContactTag::where('team_id', Auth::user()->currentTeam->id)->get()->toArray(); }
    
    #[Computed]
    public function approvedTemplates() { return \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)->where('status', 'APPROVED')->get()->toArray(); }
    
    #[Computed]
    public function availableFlows() { return \App\Models\Flow::where('team_id', Auth::user()->currentTeam->id)->get()->toArray(); }
    
    #[Computed]
    public function availableKnowledgeBaseSources() { return \App\Models\KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->whereIn('status', [\App\Models\KnowledgeBaseSource::STATUS_READY, 'indexed'])->get()->toArray(); }
    
    #[Computed]
    public function availableCampaigns() { return \App\Models\Campaign::where('team_id', Auth::user()->currentTeam->id)->get()->toArray(); }
    
    #[Computed]
    public function availableUsers() { return Auth::user()->currentTeam->users()->get()->toArray(); }
    
    #[Computed]
    public function availablePipelines() { return \App\Models\Pipeline::where('team_id', Auth::user()->currentTeam->id)->with('stages')->get()->toArray(); }
    
    #[Computed]
    public function availableWorkflows() { return \App\Models\Automation::where('team_id', Auth::user()->currentTeam->id)->where('is_active', true)->get()->toArray(); }
    
    #[Computed]
    public function availableEmailTemplates() { return \App\Models\EmailTemplate::all()->toArray(); }

    #[Computed]
    public function risks()
    {
        $risks = [];
        if ($this->triggerType === 'user_starts_conversation') {
            $risks[] = [
                'level' => 'high',
                'description' => 'Broad Trigger: This will fire for EVERY new conversation.',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ];
        }
        $hasExternal = collect($this->nodes)->contains(fn ($n) => in_array($n['type'] ?? '', ['openai', 'webhook']));
        if ($hasExternal) {
            $risks[] = [
                'level' => 'medium',
                'description' => 'External Dependencies: Flow relies on OpenAI or Webhooks which can fail or incur costs.',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
            ];
        }
        if (count($this->nodes) > 15) {
            $risks[] = [
                'level' => 'low',
                'description' => 'Large Flow: Complex logic might be harder to debug if something goes wrong.',
                'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2',
            ];
        }

        return $risks;
    }

    public function mount($automationId = null)
    {
        Log::info('AutomationBuilder MOUNT', [
            'automationId' => $automationId,
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->currentTeam->id ?? 'null',
        ]);

        $this->debugMode = session('automation_debug_mode', false);
        $this->debugLogs = session('automation_debug_logs', []);
        $this->logDebug('Component Mounting', ['automationId' => $automationId]);

        Gate::authorize('chat-access');

        // We no longer populate them in mount to keep state lean
        // They will be fetched via #[Computed] properties when needed in the view

        $team = Auth::user()->currentTeam;
        if ($automationId) {
            $automation = Automation::where('team_id', $team->id)->findOrFail($automationId);
            $this->automationId = $automation->id;
            $this->name = $automation->name;
            $this->triggerType = $automation->trigger_type ?? 'keyword';
            $this->triggerConfig = array_merge($this->triggerConfig, $automation->trigger_config ?? []);

            $flowData = $automation->flow_data ?? ['nodes' => [], 'edges' => []];
            $this->nodes = isset($flowData['nodes']) ? array_values($flowData['nodes']) : [];
            $this->edges = isset($flowData['edges']) ? array_values($flowData['edges']) : [];
            $this->version = $automation->version ?? 1;
            $this->lastPublishedAt = $automation->last_published_at;
            $this->publishLog = $automation->publish_log ?? [];
            $this->triggerKeywordsString = implode(', ', $this->triggerConfig['keywords'] ?? []);
        } else {
            $tplKey = request()->query('template');
            $tpl = $tplKey ? \App\Services\Automations\TemplateLibrary::getAll()[$tplKey] ?? null : null;

            if ($tpl) {
                $this->nodes = $tpl['nodes'];
                $this->edges = $tpl['edges'];
                $this->name = $tpl['name'].' '.date('Y-m-d H:i');
            } else {
                $this->nodes = [['id' => 'Start', 'type' => 'trigger', 'x' => 50, 'y' => 50, 'data' => ['label' => 'Start']]];
                $this->name = 'Untitled Automation '.date('Y-m-d H:i');
            }
        }

        $this->updateNodeData();
        $this->runValidation();
    }

    protected function logDebug($message, $data = [])
    {
        $entry = ['time' => date('H:i:s'), 'message' => $message, 'data' => $data];
        array_unshift($this->debugLogs, $entry);
        if (count($this->debugLogs) > 50) {
            array_pop($this->debugLogs);
        }
        session(['automation_debug_logs' => $this->debugLogs]);
    }

    public function updatedDebugMode($value)
    {
        session(['automation_debug_mode' => $value]);
    }

    public function addCard()
    {
        $this->nodeCards[] = ['image_url' => '', 'title' => '', 'sub_title' => '', 'buttons' => []];
        $this->updateNodeData();
    }

    public function removeCard($index)
    {
        unset($this->nodeCards[$index]);
        $this->nodeCards = array_values($this->nodeCards);
        $this->updateNodeData();
    }

    public function addOption()
    {
        $this->nodeOptions[] = ['id' => uniqid('opt-'), 'label' => 'New Option'];
        $this->updateNodeData();
    }

    public function removeOption($index)
    {
        unset($this->nodeOptions[$index]);
        $this->nodeOptions = array_values($this->nodeOptions);
        $this->updateNodeData();
    }

    public function addRule()
    {
        $idx = $this->getNodeIndex($this->selectedNodeId);
        if ($idx === null) {
            return;
        }
        $rules = $this->nodes[$idx]['data']['rules'] ?? [];
        $rules[] = ['variable' => '', 'operator' => 'eq', 'value' => '', 'label' => 'Rule '.(count($rules) + 1)];
        $this->nodes[$idx]['data']['rules'] = $rules;
        $this->nodeOptions = $rules;
        $this->updateNodeData();
    }

    public function removeRule($index)
    {
        $idx = $this->getNodeIndex($this->selectedNodeId);
        if ($idx === null) {
            return;
        }
        $rules = $this->nodes[$idx]['data']['rules'] ?? [];
        unset($rules[$index]);
        $rules = array_values($rules);
        $this->nodes[$idx]['data']['rules'] = $rules;
        $this->nodeOptions = $rules;
        $this->updateNodeData();
    }

    protected function getNodeIndex($nodeId): ?int
    {
        foreach ($this->nodes as $i => $node) {
            if ($node['id'] === $nodeId) {
                return $i;
            }
        }

        return null;
    }

    public function addSubTrigger()
    {
        $this->triggerConfig['triggers'][] = ['type' => 'keyword', 'keywords' => []];
        $this->updateNodeData();
    }

    public function removeSubTrigger($index)
    {
        unset($this->triggerConfig['triggers'][$index]);
        $this->triggerConfig['triggers'] = array_values($this->triggerConfig['triggers']);
        $this->updateNodeData();
    }

    public function duplicateNode($id)
    {
        $node = collect($this->nodes)->firstWhere('id', $id);
        if (! $node || ($node['type'] ?? '') === 'trigger') {
            return;
        }

        $newNode = $node;
        $newNode['id'] = uniqid('node-');
        $newNode['x'] += 50;
        $newNode['y'] += 50;
        $newNode['data']['label'] = ($newNode['data']['label'] ?? '').' (Copy)';

        $this->nodes[] = $newNode;
        $this->updateNodeData();
        $this->selectNode($newNode['id']);
    }

    public function updated($property)
    {
        // 1. Skip heavy logic for UI-only state (e.g., modals, search)
        $uiState = [
            'selectedNodeId', 'selectedEdgeIndex', 'isDirty', 'debugLogs', 
            'validationIssues', 'showPublishModal', 'showErrorModal', 
            'showTemplatesModal', 'showTestModal', 'publishNote',
            'testContactSearch', 'templateSearch', 'selectedIndustry', 'selectedUseCase'
        ];

        if (in_array($property, $uiState)) {
            return;
        }

        // 2. Logic for trigger keywords string sync
        if ($property === 'triggerKeywordsString') {
            $this->triggerConfig['keywords'] = array_filter(array_map('trim', explode(',', $this->triggerKeywordsString)));
            $this->updateNodeData();
        }

        // 3. Logic for node data
        if (str_starts_with($property, 'node') || str_starts_with($property, 'triggerConfig')) {
            $this->updateNodeData();
        }

        $this->runValidation();
        $this->isDirty = true;
    }

    #[Layout('components.layouts.app', ['fullscreen' => true])]
    public function render()
    {
        return view('livewire.automations.automation-builder');
    }
}
