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
    public $triggerKeyword;
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

    public function mount($id = null)
    {
        if ($id) {
            $automation = Automation::find($id);
            $this->automationId = $automation->id;
            $this->name = $automation->name;
            // Assuming trigger_config is { "keywords": ["hi"] }
            $this->triggerKeyword = $automation->trigger_config['keywords'][0] ?? '';

            $flowData = $automation->flow_data ?? ['nodes' => [], 'edges' => []];
            $this->nodes = $flowData['nodes'] ?? [];
            $this->edges = $flowData['edges'] ?? [];
        } else {
            // Default Start Node
            $this->nodes = [
                ['id' => 'Start', 'type' => 'trigger', 'x' => 50, 'y' => 50, 'data' => ['label' => 'Start']]
            ];
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'triggerKeyword' => 'required|string|max:50',
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
                'trigger_type' => 'keyword',
                'trigger_config' => ['keywords' => [strtolower($this->triggerKeyword)]],
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
                } elseif (in_array($type, ['image', 'video', 'audio', 'file'])) {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeText = $data['caption'] ?? '';
                } elseif ($type === 'interactive_button') {
                    $this->nodeText = $data['text'] ?? '';
                    // Flatten buttons for simple UI editor (complex object editing needed for real app)
                    $this->nodeOptions = collect($data['buttons'] ?? [])->pluck('title')->toArray();
                } elseif ($type === 'interactive_list') {
                    $this->nodeText = $data['text'] ?? '';
                } elseif ($type === 'user_input') {
                    $this->nodeText = $data['question'] ?? '';
                    $this->nodeSaveTo = $data['variable'] ?? '';
                } elseif ($type === 'openai') {
                    $this->nodeText = $data['prompt'] ?? '';
                    $this->nodeSaveTo = $data['save_to'] ?? '';
                } elseif ($type === 'template') {
                    $this->nodeText = $data['template_name'] ?? '';
                } elseif ($type === 'webhook') {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeMethod = $data['method'] ?? 'POST';
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

                if ($type === 'text') {
                    $node['data']['text'] = $this->nodeText;
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
                } elseif ($type === 'webhook') {
                    $node['data']['url'] = $this->nodeUrl;
                    $node['data']['method'] = $this->nodeMethod;
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
