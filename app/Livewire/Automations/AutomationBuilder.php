<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Livewire\Automations\Traits\HasNodes;
use App\Livewire\Automations\Traits\HasValidation;
use App\Livewire\Automations\Traits\HasNodeEditing;
use App\Livewire\Automations\Traits\HasPersistence;

class AutomationBuilder extends Component
{
    use WithFileUploads, HasNodes, HasValidation, HasNodeEditing, HasPersistence;

    public $automationId;
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
    public $nodeLanguage = 'en'; 
    public $nodeOperator = 'eq'; 

    // Text Node specific
    public $nodeTyping = false;
    public $nodeDelaySeconds = 0;
    public $nodeDelayMinutes = 0;
    public $nodeDelayHours = 0;

    public $availableTags = [];
    public $approvedTemplates = [];
    public $availableFlows = [];
    public $uploadFile;
    public $availableKnowledgeBaseSources = [];
    public $nodeUseKb = false;
    public $nodeKbScope = 'all';
    public $nodeKbSourceIds = [];
    public $nodeKbStrict = true;

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

    #[Computed]
    public function risks()
    {
        $risks = [];
        if ($this->triggerType === 'user_starts_conversation') {
            $risks[] = [
                'level' => 'high',
                'description' => 'Broad Trigger: This will fire for EVERY new conversation.',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
            ];
        }
        $hasExternal = collect($this->nodes)->contains(fn($n) => in_array($n['type'] ?? '', ['openai', 'webhook']));
        if ($hasExternal) {
            $risks[] = [
                'level' => 'medium',
                'description' => 'External Dependencies: Flow relies on OpenAI or Webhooks which can fail or incur costs.',
                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'
            ];
        }
        if (count($this->nodes) > 15) {
            $risks[] = [
                'level' => 'low',
                'description' => 'Large Flow: Complex logic might be harder to debug if something goes wrong.',
                'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'
            ];
        }
        return $risks;
    }

    public function mount($automationId = null)
    {
        Log::info("AutomationBuilder MOUNT", [
            'automationId' => $automationId,
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->currentTeam->id ?? 'null'
        ]);

        $this->debugMode = session('automation_debug_mode', false);
        $this->debugLogs = session('automation_debug_logs', []);
        $this->logDebug('Component Mounting', ['automationId' => $automationId]);
        
        Gate::authorize('chat-access'); 
        
        $team = Auth::user()->currentTeam;
        if (!$team) {
             // Fallback for tests or users without a team
             $this->availableTags = [];
             $this->approvedTemplates = [];
             $this->availableFlows = [];
             $this->availableKnowledgeBaseSources = [];
             return;
        }

        $this->availableTags = \App\Models\ContactTag::where('team_id', $team->id)->get()->toArray();
        $this->approvedTemplates = \App\Models\WhatsappTemplate::where('team_id', $team->id)
            ->where('status', 'APPROVED')
            ->get()->toArray();
        $this->availableFlows = \App\Models\Flow::where('team_id', $team->id)->get()->toArray();
        $this->availableKnowledgeBaseSources = \App\Models\KnowledgeBaseSource::where('team_id', $team->id)
            ->whereIn('status', [\App\Models\KnowledgeBaseSource::STATUS_READY, 'indexed'])
            ->get()->toArray();

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
            $this->nodes = [['id' => 'Start', 'type' => 'trigger', 'x' => 50, 'y' => 50, 'data' => ['label' => 'Start']]];
            $this->name = 'Untitled Automation ' . date('Y-m-d H:i');
        }

        $this->updateNodeData();
        $this->runValidation();
    }

    protected function logDebug($message, $data = [])
    {
        $entry = ['time' => date('H:i:s'), 'message' => $message, 'data' => $data];
        array_unshift($this->debugLogs, $entry);
        if (count($this->debugLogs) > 50) array_pop($this->debugLogs);
        session(['automation_debug_logs' => $this->debugLogs]);
    }

    public function updatedDebugMode($value)
    {
        session(['automation_debug_mode' => $value]);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.automations.automation-builder');
    }
}
