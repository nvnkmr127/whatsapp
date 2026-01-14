<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class AutomationBuilder extends Component
{
    public $automationId;
    public $name;
    // Trigger Properties
    public $triggerType = 'keyword';
    public $triggerConfig = [];

    public $nodes = []; // [ {id, type, x, y, data} ]
    public $edges = []; // [ {source, target} ]

    public $selectedNodeId = null;
    public $selectedEdgeIndex = null;
    public $edgeCondition = '';

    // Node editing properties
    public $nodeText = '';
    public $nodeUrl = '';
    public $nodeMethod = 'GET';
    public $nodeSaveTo = '';
    public $nodeHours = 0;
    public $nodeMinutes = 0;

    public $nodeOptions = [];
    public $newOption = '';

    // Advanced Node properties
    public $nodeHeaders = []; // [['key' => '', 'value' => '']]
    public $nodeJson = '';
    public $nodeModel = 'gpt-4o';

    // Text Node specific
    public $nodeTyping = false;
    public $nodeDelaySeconds = 0;
    public $nodeDelayMinutes = 0;
    public $nodeDelayHours = 0;

    public $availableTags = [];

    public function mount($id = null)
    {
        $this->availableTags = \App\Models\ContactTag::where('team_id', Auth::user()->currentTeam->id)->get()->toArray();

        if ($id) {
            $automation = Automation::find($id);
            $this->automationId = $automation->id;
            $this->name = $automation->name;

            $this->triggerType = $automation->trigger_type ?? 'keyword';
            $this->triggerConfig = $automation->trigger_config ?? [];

            // Backward compatibility
            if ($this->triggerType === 'keyword' && empty($this->triggerConfig['keywords']) && !empty($automation->trigger_config['keywords'])) {
                $this->triggerConfig = $automation->trigger_config;
            }

            $flowData = $automation->flow_data ?? ['nodes' => [], 'edges' => []];
            $this->nodes = $flowData['nodes'] ?? [];
            $this->edges = $flowData['edges'] ?? [];
        } else {
            // Default Start Node
            $this->nodes = [
                ['id' => 'Start', 'type' => 'trigger', 'x' => 50, 'y' => 50, 'data' => ['label' => 'Start']]
            ];
            // Default Trigger Config
            $this->triggerConfig = ['keywords' => [], 'is_regex' => false];
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'nodes' => 'required|array|min:1',
        ], [
            'nodes.required' => 'The automation flow cannot be empty.',
            'nodes.min' => 'Please add at least one node to the automation.',
        ]);

        try {
            $data = [
                'team_id' => Auth::user()->currentTeam->id,
                'name' => $this->name,
                'is_active' => true,
                'trigger_type' => $this->triggerType,
                'trigger_config' => $this->triggerConfig,
                'flow_data' => [
                    'nodes' => $this->nodes,
                    'edges' => $this->edges
                ]
            ];

            if ($this->automationId) {
                $automation = Automation::find($this->automationId);
                $automation->update($data);
            } else {
                $automation = Automation::create($data);
                $this->automationId = $automation->id;
            }

            session()->flash('success', 'Automation saved successfully!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Automation Save Error: ' . $e->getMessage());
            $this->addError('base', 'An error occurred while saving the automation. Please try again.');
        }
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
        }

        $this->nodes[] = [
            'id' => $id,
            'type' => $type,
            'x' => 100 + count($this->nodes) * 20, // Stagger new nodes
            'y' => 100 + count($this->nodes) * 20,
            'data' => $data
        ];
        $this->nodes = array_values($this->nodes); // Ensure array keys are reset
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
    }

    public function selectNode($id)
    {
        $this->selectedNodeId = $id;
        $this->selectedEdgeIndex = null;

        // Reset Common Fields
        $this->nodeText = '';
        $this->nodeUrl = '';
        $this->nodeMethod = 'GET';
        $this->nodeSaveTo = '';
        $this->nodeOptions = [];

        foreach ($this->nodes as $node) {
            if ($node['id'] === $id) {
                $data = $node['data'] ?? [];
                $type = $node['type'];

                if ($type === 'text') {
                    $this->nodeText = $data['text'] ?? '';
                    $this->nodeTyping = $data['typing'] ?? false;
                    $this->nodeDelaySeconds = $data['delay_seconds'] ?? 0;
                    $this->nodeDelayMinutes = $data['delay_minutes'] ?? 0;
                    $this->nodeDelayHours = $data['delay_hours'] ?? 0;
                } elseif ($type === 'trigger') {
                    // Start Node - Trigger Config
                    // The properties are on the Component itself ($this->triggerType, $this->triggerConfig)
                    // But we might want to populate local state if we use it for editing
                } elseif (in_array($type, ['image', 'video', 'audio', 'file'])) {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeText = $data['caption'] ?? '';
                } elseif ($type === 'interactive_button') {
                    $this->nodeText = $data['text'] ?? '';
                    // Flatten buttons for simple UI editor
                    $this->nodeOptions = collect($data['buttons'] ?? [])->pluck('title')->toArray();
                } elseif ($type === 'interactive_list') {
                    $this->nodeText = $data['text'] ?? '';
                } elseif ($type === 'user_input') {
                    $this->nodeText = $data['question'] ?? '';
                    $this->nodeSaveTo = $data['variable'] ?? '';
                } elseif ($type === 'openai') {
                    $this->nodeText = $data['prompt'] ?? '';
                    $this->nodeSaveTo = $data['save_to'] ?? '';
                    $this->nodeModel = $data['model'] ?? 'gpt-4o';
                } elseif ($type === 'template') {
                    $this->nodeText = $data['template_name'] ?? '';
                } elseif ($type === 'webhook') {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeMethod = $data['method'] ?? 'POST';
                    $this->nodeHeaders = $data['headers'] ?? [];
                    $this->nodeJson = $data['json_body'] ?? '';
                } elseif ($type === 'delay') {
                    $this->nodeText = $data['value'] ?? '5';
                }
                break;
            }
        }
    }

    public function updateNodeData()
    {
        foreach ($this->nodes as &$node) {
            if ($node['id'] === $this->selectedNodeId) {
                $type = $node['type'];

                if ($type === 'trigger') {
                    $node['data']['label'] = ucfirst(str_replace(['_', 'trigger'], [' ', ''], $this->triggerType)) . ' Trigger';
                } elseif ($type === 'text') {
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
                    foreach ($this->nodeOptions as $k => $opt) {
                        $buttons[] = ['id' => 'btn-' . $k, 'title' => $opt];
                    }
                    $node['data']['buttons'] = $buttons;
                } elseif ($type === 'user_input') {
                    $node['data']['question'] = $this->nodeText;
                    $node['data']['variable'] = $this->nodeSaveTo;
                } elseif ($type === 'openai') {
                    $node['data']['prompt'] = $this->nodeText;
                    $node['data']['save_to'] = $this->nodeSaveTo;
                    $node['data']['model'] = $this->nodeModel;
                } elseif ($type === 'webhook') {
                    $node['data']['url'] = $this->nodeUrl;
                    $node['data']['method'] = $this->nodeMethod;
                    $node['data']['headers'] = $this->nodeHeaders;
                    $node['data']['json_body'] = $this->nodeJson;
                } elseif ($type === 'template') {
                    $node['data']['template_name'] = $this->nodeText;
                } elseif ($type === 'delay') {
                    $node['data']['value'] = $this->nodeText;
                }
            }
        }
    }

    public function addOption()
    {
        if (!empty($this->newOption)) {
            $this->nodeOptions[] = $this->newOption;
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
    }

    public function removeTriggerKeyword($index)
    {
        if (isset($this->triggerConfig['keywords'][$index])) {
            unset($this->triggerConfig['keywords'][$index]);
            $this->triggerConfig['keywords'] = array_values($this->triggerConfig['keywords']);
        }
    }

    public function addStartTag()
    {
        if (!isset($this->triggerConfig['add_tags'])) {
            $this->triggerConfig['add_tags'] = [];
        }
        $this->triggerConfig['add_tags'][] = '';
    }

    public function removeStartTag($index)
    {
        if (isset($this->triggerConfig['add_tags'][$index])) {
            unset($this->triggerConfig['add_tags'][$index]);
            $this->triggerConfig['add_tags'] = array_values($this->triggerConfig['add_tags']);
        }
    }

    public function addRemoveTag()
    {
        if (!isset($this->triggerConfig['remove_tags'])) {
            $this->triggerConfig['remove_tags'] = [];
        }
        $this->triggerConfig['remove_tags'][] = '';
    }

    public function removeRemoveTag($index)
    {
        if (isset($this->triggerConfig['remove_tags'][$index])) {
            unset($this->triggerConfig['remove_tags'][$index]);
            $this->triggerConfig['remove_tags'] = array_values($this->triggerConfig['remove_tags']);
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
    }

    #[Layout('layouts.builder')]
    public function render()
    {
        return view('livewire.automations.automation-builder');
    }
}
