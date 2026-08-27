<?php

namespace App\Livewire\Automations\Traits;

trait HasNodes
{
    public function calculateStepMetadata()
    {
        $nodeMetadata = [];
        $edgeMetadata = [];
        $queue = [];

        // Find trigger node
        $triggerNode = collect($this->nodes)->firstWhere('type', 'trigger') ?? collect($this->nodes)->first();
        if (! $triggerNode) {
            return;
        }

        $queue[] = ['id' => $triggerNode['id']];
        $order = 1;

        while (! empty($queue)) {
            $current = array_shift($queue);
            $nodeId = $current['id'];

            if (isset($nodeMetadata[$nodeId])) {
                continue;
            }

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
            'edges' => $edgeMetadata,
        ];
    }

    public function addNode($type)
    {
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
                    ['title' => 'Section 1', 'rows' => [['id' => 'opt1', 'title' => 'Option 1', 'description' => '']]],
                ];
                break;
            case 'interactive_button':
                $data['text'] = 'Please choose one:';
                $data['buttons'] = [
                    ['id' => 'btn1', 'title' => 'Yes'],
                    ['id' => 'btn2', 'title' => 'No'],
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
                        'buttons' => [['id' => uniqid('btn-'), 'type' => 'reply', 'title' => 'Button 1']],
                    ],
                ];
                break;
            case 'tag_contact':
                $data['tag'] = 'Lead';
                break;
            case 'ab_split':
                $data['ratio'] = 50;
                $data['label'] = 'A/B Split Test';
                break;
            case 'send_email':
                $data['template_name'] = '';
                $data['subject'] = 'Hello';
                $data['body'] = '';
                break;
            case 'create_deal':
                $data['pipeline_id'] = '';
                $data['title'] = 'New Deal';
                break;
            case 'assign_to_agent':
                $data['user_id'] = 'round_robin';
                break;
            case 'create_crm_task':
                $data['title'] = 'New Task';
                $data['due_days'] = 1;
                $data['priority'] = 'medium';
                break;
            case 'set_variable':
                $data['key'] = '';
                $data['operation'] = 'set';
                $data['value'] = '';
                break;
            case 'loop_over_items':
                $data['items_path'] = '';
                break;
            case 'split_by_condition':
                $data['rules'] = [['variable' => '', 'operator' => 'eq', 'value' => '']];
                break;
            case 'wait_until':
                $data['value'] = 5;
                $data['time_unit'] = 'minutes';
                break;
            case 'rate_limit_gate':
                $data['window_hours'] = 24;
                break;
            case 'sub_flow':
                $data['target_flow_id'] = '';
                break;
            case 'tag_contact':
                $data['action'] = 'add';
                $data['tag'] = '';
                break;
            case 'handover':
                $data['label'] = 'Human Handoff';
                break;
            case 'google_sheets':
                $data['spreadsheet_id'] = '';
                $data['sheet_name'] = 'Sheet1';
                $data['action'] = 'append_row';
                $data['mapping'] = [];
                break;
            case 'stop_flow':
                $data['label'] = 'End Flow';
                break;
            case 'note':
                $data['label'] = '📌 Note';
                $data['content'] = 'Add a note here...';
                $data['color'] = 'yellow';
                break;
            case 'wait_for_event':
                $data['label'] = 'Wait for Event';
                $data['event_type'] = 'keyword';
                $data['event_value'] = '';
                $data['timeout_hours'] = 24;
                $data['timeout_action'] = 'continue';
                break;
            case 'retry':
                $data['label'] = 'Retry on Failure';
                $data['max_retries'] = 3;
                $data['retry_delay_minutes'] = 5;
                $data['target_node_type'] = 'webhook';
                break;
            case 'payment':
                $data['label'] = 'Collect Payment';
                $data['provider'] = 'razorpay';
                $data['amount_variable'] = '';
                $data['currency'] = 'INR';
                $data['description'] = 'Payment';
                $data['save_to'] = 'payment_status';
                break;
            case 'update_contact':
                $data['label'] = 'Update Contact Field';
                $data['field'] = 'name';
                $data['value'] = '';
                break;
            case 'tag_contact':
                $data['label'] = 'Tag / Untag Contact';
                $data['action'] = 'add';
                $data['tag'] = '';
                break;
            case 'catalog_message':
                $data['label'] = 'Product Catalog';
                $data['catalog_id'] = '';
                $data['product_retailer_ids'] = [];
                $data['header_text'] = 'Check out our products';
                $data['body_text'] = 'Browse and select the products you need.';
                $data['footer_text'] = '';
                $data['send_type'] = 'multi_product'; // or 'single_product'
                $data['section_title'] = 'Our Products';
                break;
        }

        $newId = uniqid('node-');
        $this->nodes[] = [
            'id' => $newId,
            'type' => $type,
            'x' => 150 + (count($this->nodes) % 5) * 350,
            'y' => 100 + intdiv(count($this->nodes), 5) * 200,
            'data' => $data,
        ];
        $this->nodes = array_values($this->nodes);
        $this->pushHistory();
        $this->runValidation();
        $this->selectNode($newId);
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
        // A trigger is an entry point — it can't be the target of an edge, and self-loops are invalid.
        if ($source === $target) {
            return;
        }
        $targetNode = collect($this->nodes)->firstWhere('id', $target);
        if (($targetNode['type'] ?? '') === 'trigger') {
            return;
        }

        foreach ($this->edges as $edge) {
            if ($edge['source'] == $source && $edge['target'] == $target) {
                return;
            }
        }
        $this->edges[] = ['source' => $source, 'target' => $target, 'condition' => ''];
        $this->edges = array_values($this->edges);
        $this->pushHistory();
        $this->runValidation();
    }

    public function deleteNode($id)
    {
        if (($node = collect($this->nodes)->firstWhere('id', $id)) && ($node['type'] ?? '') === 'trigger') {
            return;
        }
        $this->pushHistory();
        $this->nodes = collect($this->nodes)->filter(fn ($n) => $n['id'] !== $id)->values()->toArray();
        $this->edges = collect($this->edges)->filter(fn ($e) => $e['source'] !== $id && $e['target'] !== $id)->values()->toArray();
        if ($this->selectedNodeId === $id) {
            $this->selectedNodeId = null;
        }
        $this->runValidation();
    }

    public function deleteEdge($index)
    {
        $this->pushHistory();
        unset($this->edges[$index]);
        $this->edges = array_values($this->edges);
        $this->runValidation();
    }

    // NOTE: duplicateNode() lives on the AutomationBuilder component itself; a duplicate here
    // would be silently shadowed by the class method, so it was removed.
}
