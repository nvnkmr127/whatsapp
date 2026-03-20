<?php

namespace App\Livewire\Automations\Traits;

use Illuminate\Support\Collection;

trait HasNodes
{
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

    public function addNode($type)
    {
        $id = uniqid();
        $data = ['label' => ucfirst(str_replace('_', ' ', $type)), 'tag' => ''];

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
                $data['caption'] = '';
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
                $data['components'] = [];
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
                $data['expected_type'] = 'string';
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
            case 'tag_contact':
                $data['tag'] = 'Lead';
                break;
            case 'ab_split':
                $data['ratio'] = 50;
                $data['label'] = 'A/B Split Test';
                break;
        }

        $this->nodes[] = [
            'id' => $id,
            'type' => $type,
            'x' => 100 + count($this->nodes) * 20,
            'y' => 100 + count($this->nodes) * 20,
            'data' => $data
        ];
        $this->nodes = array_values($this->nodes);
        $this->runValidation();
    }

    public function updateNodePosition($id, $x, $y)
    {
        foreach ($this->nodes as &$node) {
            if ($node['id'] === $id) {
                $node['x'] = $x;
                $node['y'] = $y;
            }
        }
    }

    public function addEdge($source, $target)
    {
        foreach ($this->edges as $edge) {
            if ($edge['source'] == $source && $edge['target'] == $target)
                return;
        }
        $this->edges[] = ['source' => $source, 'target' => $target, 'condition' => ''];
        $this->edges = array_values($this->edges);
        $this->runValidation();
    }

    public function deleteNode($id)
    {
        $this->nodes = collect($this->nodes)->filter(fn($n) => $n['id'] !== $id)->values()->toArray();
        $this->edges = collect($this->edges)->filter(fn($e) => $e['source'] !== $id && $e['target'] !== $id)->values()->toArray();
        if ($this->selectedNodeId === $id) {
            $this->selectedNodeId = null;
        }
        $this->runValidation();
    }

    public function deleteEdge($index)
    {
        unset($this->edges[$index]);
        $this->edges = array_values($this->edges);
        $this->runValidation();
    }

    public function duplicateNode()
    {
        if (!$this->selectedNodeId) return;

        $node = collect($this->nodes)->firstWhere('id', $this->selectedNodeId);
        if (!$node) return;

        $newNode = $node;
        $newNode['id'] = uniqid();
        $newNode['x'] += 50;
        $newNode['y'] += 50;
        
        $this->nodes[] = $newNode;
        $this->selectNode($newNode['id']);
        $this->runValidation();
    }
}
