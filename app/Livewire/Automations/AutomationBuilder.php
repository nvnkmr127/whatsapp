<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;

class AutomationBuilder extends Component
{
    use WithFileUploads;
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
    ];
    public $triggerKeywordsString = ''; // Helper for comma-separated keywords

    public $nodes = []; // [ {id, type, x, y, data} ]
    public $edges = []; // [ {source, target} ]
    public $stepMetadata = []; // [ nodeId => {stepNumber, isBranch, isLoop} ]

    public $selectedNodeId = null;
    public $selectedEdgeIndex = null;
    public $edgeCondition = '';

    // Node editing properties
    public $nodeLabel = '';
    public $nodeText = '';
    public $nodeButtonText = ''; // For interactive_list
    public $nodeUrl = '';
    public $nodeMethod = 'GET';
    public $nodeSaveTo = '';
    public $nodeHours = 0;
    public $nodeMinutes = 0;

    public $nodeOptions = [];
    public $newOption = '';

    // Carousel properties
    public $nodeCards = []; // [['title' => '', 'description' => '', 'image' => '', 'buttons' => []]]

    // Advanced Node properties
    public $nodeHeaders = []; // [['key' => '', 'value' => '']]
    public $nodeJson = '';
    public $nodeContacts = []; // [['name' => '', 'phone' => '']]
    public $nodeDelayValue = 5;
    public $nodeDelayUnit = 'seconds';

    public $showErrorModal = false;

    public $nodeModel = 'gpt-4o';
    public $nodeLanguage = 'en'; // For templates
    public $nodeOperator = 'eq'; // For conditions

    // Text Node specific
    public $nodeTyping = false;
    public $nodeDelaySeconds = 0;
    public $nodeDelayMinutes = 0;
    public $nodeDelayHours = 0;

    public $availableTags = [];
    public $approvedTemplates = [];
    public $availableFlows = [];
    public $uploadFile; // For media uploads
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

    // CRM / Contact Properties
    public $nodeProvider = '';
    public $nodeAction = '';
    public $debugMode = false;
    public $debugLogs = []; // History of actions

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

        $results = (new \App\Services\AutomationValidationService())->validate($automation);
        $this->validationIssues = $results['issues'];
        $this->isActivatable = $results['is_activatable'];

        $this->calculateStepMetadata();
    }

    public function calculateStepMetadata()
    {
        $nodeMetadata = [];
        $edgeMetadata = [];
        $queue = [];

        // Find trigger node
        $triggerNode = collect($this->nodes)->firstWhere('type', 'trigger') ?? collect($this->nodes)->first();
        if (!$triggerNode)
            return;

        $queue[] = ['id' => $triggerNode['id']];
        $order = 1;

        while (!empty($queue)) {
            $current = array_shift($queue);
            $nodeId = $current['id'];

            if (isset($nodeMetadata[$nodeId]))
                continue;

            $nodeMetadata[$nodeId] = [
                'step' => $order++,
                'isBranch' => false,
                'isLoop' => false,
            ];

            $outgoing = collect($this->edges);
            $count = 0;
            foreach ($outgoing as $index => $edge) {
                if ($edge['source'] === $nodeId) {
                    $count++;
                    $targetId = $edge['target'];

                    if (isset($nodeMetadata[$targetId])) {
                        $edgeMetadata[$index] = ['isLoop' => true];
                        $nodeMetadata[$nodeId]['isLoop'] = true;
                    } else {
                        $queue[] = ['id' => $targetId];
                    }
                }
            }
            $nodeMetadata[$nodeId]['isBranch'] = $count > 1;
        }

        $this->stepMetadata = [
            'nodes' => $nodeMetadata,
            'edges' => $edgeMetadata
        ];
    }



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
        \Illuminate\Support\Facades\Log::info("AutomationBuilder MOUNT", [
            'automationId' => $automationId,
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->currentTeam->id ?? 'null'
        ]);

        $this->debugMode = session('automation_debug_mode', false);
        $this->debugLogs = session('automation_debug_logs', []);

        $this->logDebug('Component Mounting', ['automationId' => $automationId]);
        \Illuminate\Support\Facades\Gate::authorize('chat-access'); // Using chat-access as proxy for automation access
        $this->availableTags = \App\Models\ContactTag::where('team_id', Auth::user()->currentTeam->id)->get()->toArray();
        $this->approvedTemplates = \App\Models\WhatsappTemplate::where('team_id', Auth::user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get()->toArray();
        $this->availableFlows = \App\Models\Flow::where('team_id', Auth::user()->currentTeam->id)->get()->toArray();
        $this->availableKnowledgeBaseSources = \App\Models\KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)
            ->whereIn('status', [\App\Models\KnowledgeBaseSource::STATUS_READY, 'indexed'])
            ->get()->toArray();

        if ($automationId) {
            try {
                $automation = Automation::where('team_id', Auth::user()->currentTeam->id)->findOrFail($automationId);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                \Illuminate\Support\Facades\Log::error("AutomationBuilder: Automation not found or access denied.", [
                    'id' => $automationId,
                    'team_id' => Auth::user()->currentTeam->id
                ]);
                abort(404, "Automation not found.");
            }

            $this->automationId = $automation->id;
            $this->name = $automation->name;

            $this->triggerType = $automation->trigger_type ?? 'keyword';
            $this->triggerConfig = array_merge([
                'keywords' => [],
                'is_regex' => false,
                'add_tags' => [],
                'remove_tags' => [],
                'webhook_url' => null,
                'template_name' => null,
                'button_text' => null,
            ], $automation->trigger_config ?? []);

            // Backward compatibility
            if ($this->triggerType === 'keyword' && empty($this->triggerConfig['keywords']) && !empty($automation->trigger_config['keywords'])) {
                $this->triggerConfig = $automation->trigger_config;
            }

            $flowData = $automation->flow_data ?? ['nodes' => [], 'edges' => []];

            $this->logDebug('Raw Database flow_data', [
                'raw_type' => gettype($automation->flow_data),
                'raw_content' => $automation->flow_data
            ]);

            $this->nodes = isset($flowData['nodes']) ? array_values($flowData['nodes']) : [];
            $this->edges = isset($flowData['edges']) ? array_values($flowData['edges']) : [];

            $this->version = $automation->version ?? 1;
            $this->lastPublishedAt = $automation->last_published_at;
            $this->publishLog = $automation->publish_log ?? [];

            $this->logDebug('Loaded Automation from DB', [
                'automation_id' => $automation->id,
                'nodes_count' => count($this->nodes),
                'edges_count' => count($this->edges)
            ]);

            // Sync helper string
            $this->triggerKeywordsString = implode(', ', $this->triggerConfig['keywords'] ?? []);
        } else {
            // Default Start Node
            $this->nodes = [
                ['id' => 'Start', 'type' => 'trigger', 'x' => 50, 'y' => 50, 'data' => ['label' => 'Start']]
            ];
            // Default Trigger Config
            $this->triggerConfig = [
                'keywords' => [],
                'is_regex' => false,
                'add_tags' => [],
                'remove_tags' => [],
                'webhook_url' => null
            ];
            $this->name = 'Untitled Automation ' . date('Y-m-d H:i');
        }

        $this->updateNodeData();
        $this->runValidation();
    }

    public function save($shouldActivate = false)
    {
        $this->logDebug('Save Clicked', [
            'raw_nodes_count' => count($this->nodes),
            'raw_edges_count' => count($this->edges),
            'selected_node' => $this->selectedNodeId,
            'should_activate' => $shouldActivate
        ]);

        // Flush pending changes from properties to the nodes array
        $this->updateNodeData();
        $this->runValidation();

        try {
            $this->validate([
                'name' => 'required|string|max:255',
                'nodes' => 'required|array|min:1',
            ], [
                'nodes.required' => 'The automation flow cannot be empty.',
                'nodes.min' => 'Please add at least one node to the automation.',
            ]);

            if ($shouldActivate && !$this->isActivatable) {
                $this->addError('base', 'There are critical errors in your flow. Please fix them before publishing.');
                $this->showErrorModal = true;
                return;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->showErrorModal = true;
            throw $e;
        }

        try {
            $data = [
                'team_id' => Auth::user()->currentTeam->id,
                'name' => $this->name,
                'is_active' => $shouldActivate ? true : (isset($this->automationId) ? \App\Models\Automation::find($this->automationId)->is_active : false),
                'trigger_type' => $this->triggerType,
                'trigger_config' => $this->triggerConfig,
                'version' => $this->version,
                'last_published_at' => $this->lastPublishedAt,
                'publish_log' => $this->publishLog,
                'flow_data' => [
                    'nodes' => array_values($this->nodes),
                    'edges' => array_values($this->edges)
                ]
            ];

            if ($this->debugMode) {
                $this->logDebug('Final Save Payload', [
                    'id' => $this->automationId,
                    'node_count' => count($this->nodes),
                    'edge_count' => count($this->edges),
                    'payload' => $data['flow_data']
                ]);
            }

            if ($this->automationId) {
                $automation = Automation::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->automationId);
                $automation->update($data);
                session()->flash('success', $shouldActivate ? 'Automation published successfully!' : 'Draft saved successfully!');
            } else {
                $automation = Automation::create($data);
                $this->automationId = $automation->id;
                session()->flash('success', $shouldActivate ? 'Automation created and published!' : 'Draft created successfully!');
                return redirect()->route('automations.builder', $automation->id);
            }
        } catch (\Exception $e) {
            $this->logDebug('Save Exception', ['error' => $e->getMessage()]);
            $this->addError('base', 'An error occurred while saving the automation.');
            $this->showErrorModal = true;
        }
    }

    public function publish()
    {
        $this->updateNodeData();
        $this->runValidation();

        if (!$this->isActivatable) {
            $this->addError('base', 'There are critical errors in your flow. Please fix them before publishing.');
            $this->showErrorModal = true;
            return;
        }

        $this->showPublishModal = true;
    }

    public function confirmPublish()
    {
        $this->logDebug('Confirming Publish', ['note' => $this->publishNote]);

        // If we are editing an existing one, update version
        if ($this->automationId) {
            $automation = Automation::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->automationId);
            $newVersion = ($automation->version ?? 0) + 1;
            $this->version = $newVersion;
        } else {
            // New automation starts at 1
            $this->version = 1;
        }

        $this->lastPublishedAt = now();

        $entry = [
            'version' => $this->version,
            'note' => $this->publishNote,
            'published_at' => now()->toDateTimeString(),
            'published_by' => Auth::user()->name
        ];

        // Ensure publishLog is array
        if (!is_array($this->publishLog)) {
            $this->publishLog = [];
        }
        array_unshift($this->publishLog, $entry);

        $result = $this->save(true);
        if ($result instanceof \Illuminate\Http\RedirectResponse || $result instanceof \Livewire\Features\SupportRedirects\Redirector) {
            return $result;
        }

        $this->showPublishModal = false;
        $this->publishNote = '';
    }

    public function updatedDebugMode($value)
    {
        session(['automation_debug_mode' => $value]);
    }

    protected function logDebug($message, $data = [])
    {
        $entry = [
            'time' => date('H:i:s'),
            'message' => $message,
            'data' => $data
        ];
        array_unshift($this->debugLogs, $entry); // Newest first
        if (count($this->debugLogs) > 50) {
            array_pop($this->debugLogs);
        }

        // Persist to session immediately so it survives any reload/redirect
        session(['automation_debug_logs' => $this->debugLogs]);

        // Also log to file for permanent record
        \Illuminate\Support\Facades\Log::info("DEBUG: " . $message, $data);
    }

    public function addNode($type)
    {
        $id = uniqid();
        $data = ['label' => ucfirst(str_replace('_', ' ', $type))];

        switch ($type) {
            case 'message':
            case 'text':
                $type = 'text';
                $data['text'] = 'Hello, this is a text message.';
                $data['preview_url'] = true;
                break;
            case 'image':
                $data['url'] = '';
                $data['caption'] = '';
                break;
            case 'video':
            case 'audio':
            case 'file':
                $data['url'] = '';
                $data['caption'] = ''; // Video/File can have caption
                break;
            case 'interactive_list':
                $data['text'] = 'Please select an option from the list below:';
                $data['button_text'] = 'View Options';
                $data['sections'] = [
                    ['title' => 'Section 1', 'rows' => [['id' => 'opt1', 'title' => 'Option 1', 'description' => '']]]
                ];
                break;
            case 'interactive_button':
                $data['text'] = 'Please choose one:';
                $data['buttons'] = [
                    ['id' => 'btn1', 'title' => 'Yes'],
                    ['id' => 'btn2', 'title' => 'No']
                ];
                break;
            case 'template':
                $data['template_name'] = '';
                $data['language'] = 'en';
                $data['components'] = []; // For header/body variables
                break;
            case 'location_request':
                $data['text'] = 'Please share your location.';
                break;
            case 'contact':
                $data['contacts'] = [['name' => ['formatted_name' => 'Support'], 'phones' => [['phone' => '1234567890']]]];
                break;
            case 'user_input':
                $data['question'] = 'What is your email?';
                $data['variable'] = 'email';
                $data['expected_type'] = 'string'; // string, number, email
                break;
            case 'openai':
                $data['prompt'] = 'You are a helpful assistant.';
                $data['save_to'] = 'ai_response';
                $data['model'] = 'gpt-4o';
                break;
            case 'condition':
                $data['variable'] = 'email';
                $data['operator'] = 'contains';
                $data['value'] = '@';
                break;
            case 'webhook':
                $data['url'] = 'https://api.example.com';
                $data['method'] = 'POST';
                $data['headers'] = [];
                break;
            case 'crm_sync':
                $data['provider'] = 'salesforce';
                $data['action'] = 'update_lead';
                break;
            // Defaults
            case 'delay':
                $data['time_unit'] = 'seconds';
                $data['value'] = 5;
                break;
            case 'send_flow':
                $data['flow_id'] = '';
                $data['text'] = 'Open Form';
                break;
            case 'carousel':
                $data['cards'] = [
                    [
                        'title' => 'Card Title',
                        'description' => 'Card Description',
                        'image' => '',
                        'buttons' => [['id' => uniqid('btn-'), 'type' => 'reply', 'title' => 'Button 1']]
                    ]
                ];
                break;
        }

        $this->nodes[] = [
            'id' => $id,
            'type' => $type,
            'x' => 100 + count($this->nodes) * 20, // Stagger new nodes
            'y' => 100 + count($this->nodes) * 20,
            'data' => $data
        ];
        $this->nodes = array_values($this->nodes); // Ensure array keys are reset
        $this->runValidation();
    }

    // Called by Alpine when node moves
    public function updateNodePosition($id, $x, $y)
    {
        foreach ($this->nodes as &$node) {
            if ($node['id'] === $id) {
                $node['x'] = $x;
                $node['y'] = $y;
            }
        }
    }

    // Called by Alpine when connecting
    public function addEdge($source, $target)
    {
        // prevent duplicate edges
        foreach ($this->edges as $edge) {
            if ($edge['source'] == $source && $edge['target'] == $target)
                return;
        }
        $this->edges[] = ['source' => $source, 'target' => $target, 'condition' => ''];
        $this->edges = array_values($this->edges);
        $this->runValidation();
    }

    public function selectNode($id)
    {
        $this->selectedNodeId = $id;
        $this->selectedEdgeIndex = null;

        // Reset Common Fields
        $this->nodeLabel = '';
        $this->nodeText = '';
        $this->nodeButtonText = '';
        $this->nodeUrl = '';
        $this->nodeMethod = 'GET';
        $this->nodeSaveTo = '';
        $this->nodeOptions = [];
        $this->nodeLanguage = 'en';
        $this->nodeOperator = 'eq';

        foreach ($this->nodes as $node) {
            if ($node['id'] === $id) {
                $data = $node['data'] ?? [];
                $type = $node['type'];

                if ($id) {
                    $this->nodeLabel = $data['label'] ?? ucfirst($type);
                }

                if ($type === 'text') {
                    $this->nodeText = $data['text'] ?? '';
                    $this->nodeTyping = $data['typing'] ?? false;
                    $this->nodeDelaySeconds = $data['delay_seconds'] ?? 0;
                    $this->nodeDelayMinutes = $data['delay_minutes'] ?? 0;
                    $this->nodeDelayHours = $data['delay_hours'] ?? 0;
                } elseif ($type === 'trigger') {
                    // Start Node - Trigger Config
                    $this->triggerKeywordsString = implode(', ', $this->triggerConfig['keywords'] ?? []);
                } elseif (in_array($type, ['image', 'video', 'audio', 'file'])) {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeText = $data['caption'] ?? '';
                } elseif ($type === 'interactive_button') {
                    $this->nodeText = $data['text'] ?? '';
                    // Flatten buttons for simple UI editor
                    $this->nodeOptions = collect($data['buttons'] ?? [])->map(function ($btn) {
                        return ['id' => $btn['id'], 'label' => $btn['title']];
                    })->toArray();
                } elseif ($type === 'interactive_list') {
                    $this->nodeText = $data['text'] ?? '';
                    $this->nodeButtonText = $data['button_text'] ?? 'View Options';
                    // Flatten 1st section rows for simple editing
                    $rows = $data['sections'][0]['rows'] ?? [];
                    $this->nodeOptions = collect($rows)->map(function ($row) {
                        return ['id' => $row['id'], 'label' => $row['title']];
                    })->toArray();
                } elseif ($type === 'user_input') {
                    $this->nodeText = $data['question'] ?? '';
                    $this->nodeSaveTo = $data['variable'] ?? '';
                } elseif ($type === 'openai') {
                    $this->nodeText = $data['prompt'] ?? '';
                    $this->nodeSaveTo = $data['save_to'] ?? '';
                    $this->nodeModel = $data['model'] ?? 'gpt-4o';
                    $this->nodeUseKb = $data['use_knowledge_base'] ?? false;
                    $this->nodeKbScope = $data['kb_scope'] ?? 'all';
                    $this->nodeKbSourceIds = $data['kb_source_ids'] ?? [];
                    $this->nodeKbStrict = $data['kb_strict'] ?? true;
                } elseif ($type === 'template') {
                    $this->nodeText = $data['template_name'] ?? '';
                    $this->nodeLanguage = $data['language'] ?? 'en';
                } elseif ($type === 'condition') {
                    $this->nodeSaveTo = $data['variable'] ?? '';
                    $this->nodeOperator = $data['operator'] ?? 'eq';
                    $this->nodeText = $data['value'] ?? '';
                } elseif ($type === 'webhook') {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeMethod = $data['method'] ?? 'POST';
                    $this->nodeHeaders = $data['headers'] ?? [];
                    $this->nodeJson = $data['json_body'] ?? '';
                } elseif ($type === 'delay') {
                    $this->nodeDelayValue = $data['value'] ?? 5;
                    $this->nodeDelayUnit = $data['time_unit'] ?? 'seconds';
                } elseif ($type === 'send_flow') {
                    $this->nodeSaveTo = $data['flow_id'] ?? '';
                    $this->nodeText = $data['text'] ?? 'Open Form';
                } elseif ($type === 'crm_sync') {
                    $this->nodeProvider = $data['provider'] ?? 'salesforce';
                    $this->nodeAction = $data['action'] ?? 'update_lead';
                } elseif ($type === 'location_request') {
                    $this->nodeText = $data['text'] ?? '';
                } elseif ($type === 'contact') {
                    $contacts = $data['contacts'] ?? [];
                    // Flatten for simple UI
                    $this->nodeContacts = [];
                    foreach ($contacts as $c) {
                        $this->nodeContacts[] = [
                            'name' => $c['name']['formatted_name'] ?? '',
                            'phone' => $c['phones'][0]['phone'] ?? ''
                        ];
                    }
                } elseif ($type === 'carousel') {
                    $this->nodeCards = $data['cards'] ?? [];
                }
                break;
            }
        }
    }



    // Generic hook to save any node property change immediately to the array
    public function updatedTriggerKeywordsString($value)
    {
        $this->triggerConfig['keywords'] = array_filter(array_map('trim', explode(',', $value)));
        $this->updateNodeData();
    }

    public function updated($propertyName)
    {
        // If we are editing a node property (starts with node, or trigger config)
        if (str_starts_with($propertyName, 'node') || str_starts_with($propertyName, 'triggerConfig')) {
            $this->updateNodeData();
        }
        $this->runValidation();
    }

    public function updatedNodeText($value)
    {
        // Check if currently selected node is template
        $node = collect($this->nodes)->firstWhere('id', $this->selectedNodeId);
        if ($node && $node['type'] === 'template') {
            // Find the template by name
            $template = collect($this->approvedTemplates)->firstWhere('name', $value);
            if ($template) {
                $this->nodeLanguage = $template['language'] ?? 'en';
                $this->updateNodeData(); // Save immediately
            }
        }
    }

    public function updatedUploadFile()
    {
        $this->validate([
            'uploadFile' => 'file|max:10240', // 10MB max
        ]);

        $node = collect($this->nodes)->firstWhere('id', $this->selectedNodeId);
        if (!$node)
            return;

        $type = $node['type'];
        // Validation logic based on type
        if ($type === 'image') {
            $this->validate(['uploadFile' => 'image|mimes:jpeg,png,jpg']);
        } elseif ($type === 'video') {
            $this->validate(['uploadFile' => 'mimetypes:video/mp4,video/3gpp']);
        } elseif ($type === 'audio') {
            $this->validate(['uploadFile' => 'mimetypes:audio/mpeg,audio/ogg,audio/wav']);
        }

        // Store file
        $path = $this->uploadFile->store('automation-uploads', 'public');
        $this->nodeUrl = \Illuminate\Support\Facades\Storage::url($path);

        // Reset upload input
        $this->reset('uploadFile');

        // Update node data immediately
        $this->updateNodeData();
    }

    public function updateNodeData()
    {
        // 1. Always sync trigger nodes with component state
        foreach ($this->nodes as &$node) {
            if ($node['type'] === 'trigger') {
                $node['data']['label'] = ucfirst(str_replace(['_', 'trigger'], [' ', ''], $this->triggerType)) . ' Trigger';
                $node['data']['trigger_type'] = $this->triggerType;
                $node['data']['keywords'] = $this->triggerConfig['keywords'] ?? [];
                $node['data']['add_tags'] = $this->triggerConfig['add_tags'] ?? [];
                $node['data']['remove_tags'] = $this->triggerConfig['remove_tags'] ?? [];
                $node['data']['template_name'] = $this->triggerConfig['template_name'] ?? null;
                $node['data']['button_text'] = $this->triggerConfig['button_text'] ?? null;
                $node['data']['webhook_url'] = $this->triggerConfig['webhook_url'] ?? null;
            }
        }

        // 2. Update the specifically selected node properties
        if (!$this->selectedNodeId)
            return;

        foreach ($this->nodes as &$node) {
            if ($node['id'] === $this->selectedNodeId) {
                $type = $node['type'];

                // Don't overwrite trigger label if we already set it above, 
                // but other nodes use nodeLabel
                if ($type !== 'trigger') {
                    $node['data']['label'] = $this->nodeLabel ?: ($node['data']['label'] ?? ucfirst($type));
                }

                if ($type === 'text') {
                    $node['data']['text'] = $this->nodeText;
                    $node['data']['typing'] = $this->nodeTyping;
                    $node['data']['delay_seconds'] = $this->nodeDelaySeconds;
                    $node['data']['delay_minutes'] = $this->nodeDelayMinutes;
                    $node['data']['delay_hours'] = $this->nodeDelayHours;
                } elseif (in_array($type, ['image', 'video', 'audio', 'file'])) {
                    $node['data']['url'] = $this->nodeUrl;
                    $node['data']['caption'] = $this->nodeText;
                } elseif ($type === 'interactive_button') {
                    $node['data']['text'] = $this->nodeText;
                    // Rebuild buttons from options
                    $buttons = [];
                    foreach ($this->nodeOptions as $opt) {
                        // Use existing ID or generate new if missing (legacy data)
                        $id = $opt['id'] ?? uniqid('btn-');
                        $buttons[] = ['id' => $id, 'title' => $opt['label']];
                    }
                    $node['data']['buttons'] = $buttons;
                } elseif ($type === 'interactive_list') {
                    $node['data']['text'] = $this->nodeText;
                    $node['data']['button_text'] = $this->nodeButtonText;
                    // Rebuild sections from options (Single Section Mode)
                    $rows = [];
                    foreach ($this->nodeOptions as $opt) {
                        $id = $opt['id'] ?? uniqid('row-');
                        $rows[] = ['id' => $id, 'title' => $opt['label'], 'description' => ''];
                    }
                    $node['data']['sections'] = [
                        ['title' => 'Options', 'rows' => $rows]
                    ];
                } elseif ($type === 'user_input') {
                    $node['data']['question'] = $this->nodeText;
                    $node['data']['variable'] = $this->nodeSaveTo;
                } elseif ($type === 'openai') {
                    $node['data']['prompt'] = $this->nodeText;
                    $node['data']['save_to'] = $this->nodeSaveTo;
                    $node['data']['model'] = $this->nodeModel;
                    $node['data']['use_knowledge_base'] = $this->nodeUseKb;
                    $node['data']['kb_scope'] = $this->nodeKbScope;
                    $node['data']['kb_source_ids'] = $this->nodeKbSourceIds;
                    $node['data']['kb_strict'] = $this->nodeKbStrict;
                } elseif ($type === 'webhook') {
                    $node['data']['url'] = $this->nodeUrl;
                    $node['data']['method'] = $this->nodeMethod;
                    $node['data']['headers'] = $this->nodeHeaders;
                    $node['data']['json_body'] = $this->nodeJson;
                } elseif ($type === 'template') {
                    $node['data']['template_name'] = $this->nodeText;
                    $node['data']['language'] = $this->nodeLanguage;
                } elseif ($type === 'condition') {
                    $node['data']['variable'] = $this->nodeSaveTo;
                    $node['data']['operator'] = $this->nodeOperator;
                    $node['data']['value'] = $this->nodeText;
                } elseif ($type === 'delay') {
                    $node['data']['value'] = $this->nodeDelayValue;
                    $node['data']['time_unit'] = $this->nodeDelayUnit;
                } elseif ($type === 'carousel') {
                    $node['data']['cards'] = $this->nodeCards;
                } elseif ($type === 'send_flow') {
                    $node['data']['flow_id'] = $this->nodeSaveTo;
                    $node['data']['text'] = $this->nodeText;
                } elseif ($type === 'crm_sync') {
                    $node['data']['provider'] = $this->nodeProvider;
                    $node['data']['action'] = $this->nodeAction;
                } elseif ($type === 'location_request') {
                    $node['data']['text'] = $this->nodeText;
                } elseif ($type === 'contact') {
                    $contacts = [];
                    foreach ($this->nodeContacts as $c) {
                        if (!empty($c['name'])) {
                            $contacts[] = [
                                'name' => ['formatted_name' => $c['name']],
                                'phones' => [['phone' => $c['phone']]]
                            ];
                        }
                    }
                    $node['data']['contacts'] = $contacts;
                } elseif ($type === 'carousel') {
                    $node['data']['cards'] = $this->nodeCards;
                }
            }
        }
    }

    public function addOption()
    {
        if (!empty($this->newOption)) {
            $this->nodeOptions[] = [
                'id' => uniqid($this->selectedNodeId . '_opt_'),
                'label' => $this->newOption
            ];
            $this->newOption = '';
            $this->updateNodeData();
        }
    }

    public function removeOption($index)
    {
        if (isset($this->nodeOptions[$index])) {
            unset($this->nodeOptions[$index]);
            $this->nodeOptions = array_values($this->nodeOptions); // Reindex
            $this->updateNodeData();
        }
    }

    public function updateEdgeData()
    {
        if ($this->selectedEdgeIndex !== null && isset($this->edges[$this->selectedEdgeIndex])) {
            $this->edges[$this->selectedEdgeIndex]['condition'] = $this->edgeCondition;
        }
    }

    public function duplicateNode()
    {
        if ($this->selectedNodeId) {
            $originalNode = collect($this->nodes)->firstWhere('id', $this->selectedNodeId);
            if ($originalNode) {
                $newNode = $originalNode;
                $newNode['id'] = uniqid();
                $newNode['x'] += 50;
                $newNode['y'] += 50;
                $this->nodes[] = $newNode;
            }
        }
    }

    public function addHeader()
    {
        $this->nodeHeaders[] = ['key' => '', 'value' => ''];
        $this->updateNodeData();
    }

    public function removeHeader($index)
    {
        if (isset($this->nodeHeaders[$index])) {
            unset($this->nodeHeaders[$index]);
            $this->nodeHeaders = array_values($this->nodeHeaders);
            $this->updateNodeData();
        }
    }

    public function addTriggerKeyword()
    {
        if (!isset($this->triggerConfig['keywords'])) {
            $this->triggerConfig['keywords'] = [];
        }
        $this->triggerConfig['keywords'][] = '';
        $this->updateNodeData();
    }

    public function removeTriggerKeyword($index)
    {
        if (isset($this->triggerConfig['keywords'][$index])) {
            unset($this->triggerConfig['keywords'][$index]);
            $this->triggerConfig['keywords'] = array_values($this->triggerConfig['keywords']);
        }
        $this->updateNodeData();
    }

    public function addStartTag()
    {
        if (!isset($this->triggerConfig['add_tags'])) {
            $this->triggerConfig['add_tags'] = [];
        }
        $this->triggerConfig['add_tags'][] = '';
        $this->updateNodeData();
    }

    public function removeStartTag($index)
    {
        if (isset($this->triggerConfig['add_tags'][$index])) {
            unset($this->triggerConfig['add_tags'][$index]);
            $this->triggerConfig['add_tags'] = array_values($this->triggerConfig['add_tags']);
        }
        $this->updateNodeData();
    }

    public function addRemoveTag()
    {
        if (!isset($this->triggerConfig['remove_tags'])) {
            $this->triggerConfig['remove_tags'] = [];
        }
        $this->triggerConfig['remove_tags'][] = '';
        $this->updateNodeData();
    }

    // Carousel Methods
    public function addCard()
    {
        $this->nodeCards[] = [
            'title' => 'New Card',
            'description' => '',
            'image' => '',
            'buttons' => []
        ];
        $this->updateNodeData();
    }

    public function removeCard($index)
    {
        if (isset($this->nodeCards[$index])) {
            unset($this->nodeCards[$index]);
            $this->nodeCards = array_values($this->nodeCards);
            $this->updateNodeData();
        }
    }

    public function addCardButton($cardIndex)
    {
        if (isset($this->nodeCards[$cardIndex])) {
            if (count($this->nodeCards[$cardIndex]['buttons'] ?? []) < 3) {
                $this->nodeCards[$cardIndex]['buttons'][] = [
                    'id' => uniqid('btn-'),
                    'type' => 'reply',
                    'title' => 'Button'
                ];
                $this->updateNodeData();
            }
        }
    }

    public function removeCardButton($cardIndex, $btnIndex)
    {
        if (isset($this->nodeCards[$cardIndex]['buttons'][$btnIndex])) {
            unset($this->nodeCards[$cardIndex]['buttons'][$btnIndex]);
            $this->nodeCards[$cardIndex]['buttons'] = array_values($this->nodeCards[$cardIndex]['buttons']);
            $this->updateNodeData();
        }
    }


    public function removeRemoveTag($index)
    {
        if (isset($this->triggerConfig['remove_tags'][$index])) {
            unset($this->triggerConfig['remove_tags'][$index]);
            $this->triggerConfig['remove_tags'] = array_values($this->triggerConfig['remove_tags']);
        }
        $this->updateNodeData();
    }

    public function addContact()
    {
        $this->nodeContacts[] = ['name' => '', 'phone' => ''];
        $this->updateNodeData();
    }

    public function removeContact($index)
    {
        if (isset($this->nodeContacts[$index])) {
            unset($this->nodeContacts[$index]);
            $this->nodeContacts = array_values($this->nodeContacts);
            $this->updateNodeData();
        }
    }

    public function deleteNode($id)
    {
        $this->nodes = collect($this->nodes)->reject(fn($n) => $n['id'] === $id)->values()->toArray();
        // Remove connected edges
        $this->edges = collect($this->edges)->reject(fn($e) => $e['source'] === $id || $e['target'] === $id)->values()->toArray();

        if ($this->selectedNodeId === $id) {
            $this->selectedNodeId = null;
        }
        $this->runValidation();
    }

    public function getEdgesWithPathsProperty()
    {
        return collect($this->edges)->map(function ($edge) {
            $source = collect($this->nodes)->firstWhere('id', $edge['source']);
            $target = collect($this->nodes)->firstWhere('id', $edge['target']);

            if (!$source || !$target)
                return null;

            // These terminal coordinates are used by the connection logic
            $edge['source_x'] = $source['x'] + 288 + 16; // Right handle
            $edge['source_y'] = $source['y'] + 48;
            $edge['target_x'] = $target['x'] - 16; // Left handle
            $edge['target_y'] = $target['y'] + 48;

            return $edge;
        })->filter()->values()->toArray();
    }

    #[Layout('layouts.builder')]
    public function render()
    {
        return view('livewire.automations.automation-builder');
    }
}
