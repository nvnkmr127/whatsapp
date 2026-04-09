<?php

namespace App\Livewire\Automations\Traits;

use Illuminate\Support\Facades\Storage;

trait HasNodeEditing
{
    public function selectNode($id)
    {
        $this->selectedNodeId = $id;
        $this->selectedEdgeIndex = null;

        // Reset Common Fields
        $this->nodeLabel = '';
        $this->nodeTag = '';
        $this->nodeText = '';
        $this->nodeButtonText = '';
        $this->nodeUrl = '';
        $this->nodeMethod = 'GET';
        $this->nodeSaveTo = '';
        $this->nodeOptions = [];
        $this->nodeLanguage = 'en';
        $this->nodeOperator = 'eq';
        $this->nodeRatio = 50;
        $this->nodeAction = '';
        $this->nodeProvider = '';
        $this->nodeHours = 0;
        $this->nodeDelayValue = 5;
        $this->nodeDelayUnit = 'minutes';
        $this->nodeCards = [];
        $this->nodeContacts = [];
        $this->nodeHeaders = [];
        $this->nodeJson = '';
        $this->nodeUseKb = false;
        $this->nodeKbSourceIds = [];
        $this->nodeKbScope = 'all';
        $this->nodeKbStrict = true;

        foreach ($this->nodes as $node) {
            if ($node['id'] === $id) {
                $data = $node['data'] ?? [];
                $type = $node['type'];

                if ($id) {
                    $this->nodeLabel = $data['label'] ?? ucfirst($type);
                    $this->nodeTag = $data['tag'] ?? '';
                }

                if ($type === 'text') {
                    $this->nodeText = $data['text'] ?? '';
                    $this->nodeTyping = $data['typing'] ?? false;
                    $this->nodeDelaySeconds = $data['delay_seconds'] ?? 0;
                    $this->nodeDelayMinutes = $data['delay_minutes'] ?? 0;
                    $this->nodeDelayHours = $data['delay_hours'] ?? 0;
                } elseif ($type === 'trigger') {
                    $this->triggerKeywordsString = implode(', ', $this->triggerConfig['keywords'] ?? []);
                } elseif (in_array($type, ['image', 'video', 'audio', 'file'])) {
                    $this->nodeUrl = $data['url'] ?? '';
                    $this->nodeText = $data['caption'] ?? '';
                } elseif ($type === 'interactive_button') {
                    $this->nodeText = $data['text'] ?? '';
                    $this->nodeOptions = collect($data['buttons'] ?? [])->map(function ($btn) {
                        return ['id' => $btn['id'], 'label' => $btn['title']];
                    })->toArray();
                } elseif ($type === 'interactive_list') {
                    $this->nodeText = $data['text'] ?? '';
                    $this->nodeButtonText = $data['button_text'] ?? 'View Options';
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
                    $this->nodeContacts = [];
                    foreach ($contacts as $c) {
                        $this->nodeContacts[] = [
                            'name' => $c['name']['formatted_name'] ?? '',
                            'phone' => $c['phones'][0]['phone'] ?? '',
                        ];
                    }
                } elseif ($type === 'carousel') {
                    $this->nodeCards = $data['cards'] ?? [];
                } elseif ($type === 'ab_split') {
                    $this->nodeRatio = $data['ratio'] ?? 50;
                } elseif ($type === 'send_email') {
                    $this->nodeLabel = $data['template_name'] ?? '';
                    $this->nodeTag = $data['subject'] ?? '';
                    $this->nodeText = $data['body'] ?? '';
                } elseif ($type === 'create_deal') {
                    $this->nodeProvider = $data['pipeline_id'] ?? '';
                    $this->nodeLabel = $data['title'] ?? '';
                } elseif ($type === 'assign_to_agent') {
                    $this->nodeProvider = $data['user_id'] ?? 'round_robin';
                } elseif ($type === 'create_crm_task') {
                    $this->nodeLabel = $data['title'] ?? '';
                    $this->nodeHours = $data['due_days'] ?? 1;
                    $this->nodeTag = $data['priority'] ?? 'medium';
                } elseif ($type === 'loop_over_items') {
                    $this->nodeLabel = $data['items_path'] ?? '';
                } elseif ($type === 'set_variable') {
                    $this->nodeSaveTo = $data['key'] ?? '';
                    $this->nodeAction = $data['operation'] ?? 'set';
                    $this->nodeText = $data['value'] ?? '';
                } elseif ($type === 'wait_until') {
                    $this->nodeDelayValue = $data['value'] ?? 5;
                    $this->nodeDelayUnit = $data['time_unit'] ?? 'seconds';
                } elseif ($type === 'rate_limit_gate') {
                    $this->nodeHours = $data['window_hours'] ?? 24;
                } elseif ($type === 'sub_flow') {
                    $this->nodeProvider = $data['target_flow_id'] ?? '';
                } elseif ($type === 'split_by_condition') {
                    $this->nodeOptions = $data['rules'] ?? [['variable' => '', 'operator' => 'eq', 'value' => '']];
                } elseif ($type === 'google_sheets') {
                    $this->nodeUrl = $data['spreadsheet_id'] ?? '';
                    $this->nodeTag = $data['sheet_name'] ?? 'Sheet1';
                    $this->nodeAction = $data['action'] ?? 'append_row';
                } elseif ($type === 'handover') {
                    $this->nodeProvider = $data['dept'] ?? 'default';
                }
                break;
            }
        }
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'node') || str_starts_with($propertyName, 'triggerConfig')) {
            $this->updateNodeData();
        }
        $this->runValidation();
    }

    public function updatedTriggerKeywordsString($value)
    {
        $this->triggerConfig['keywords'] = array_filter(array_map('trim', explode(',', $value)));
        $this->updateNodeData();
    }

    public function updatedNodeText($value)
    {
        $node = collect($this->nodes)->firstWhere('id', $this->selectedNodeId);
        if ($node && $node['type'] === 'template') {
            $template = collect($this->approvedTemplates)->firstWhere('name', $value);
            if ($template) {
                $this->nodeLanguage = $template['language'] ?? 'en';
                $this->updateNodeData();
            }
        }
    }

    public function updatedUploadFile()
    {
        $this->validate(['uploadFile' => 'file|max:10240']);

        $node = collect($this->nodes)->firstWhere('id', $this->selectedNodeId);
        if (! $node) {
            return;
        }

        $type = $node['type'];
        if ($type === 'image') {
            $this->validate(['uploadFile' => 'image|mimes:jpeg,png,jpg']);
        } elseif ($type === 'video') {
            $this->validate(['uploadFile' => 'mimetypes:video/mp4,video/3gpp']);
        } elseif ($type === 'audio') {
            $this->validate(['uploadFile' => 'mimetypes:audio/mpeg,audio/ogg,audio/wav']);
        }

        $path = $this->uploadFile->store('automation-uploads', 'public');
        $this->nodeUrl = Storage::url($path);

        $this->reset('uploadFile');
        $this->updateNodeData();
    }

    public function updateNodeData()
    {
        foreach ($this->nodes as &$node) {
            if ($node['type'] === 'trigger') {
                $node['data']['label'] = ucfirst(str_replace(['_', 'trigger'], [' ', ''], $this->triggerType)).' Trigger';
                $node['data']['trigger_type'] = $this->triggerType;
                $node['data']['keywords'] = $this->triggerConfig['keywords'] ?? [];
                $node['data']['add_tags'] = $this->triggerConfig['add_tags'] ?? [];
                $node['data']['remove_tags'] = $this->triggerConfig['remove_tags'] ?? [];
                $node['data']['template_name'] = $this->triggerConfig['template_name'] ?? null;
                $node['data']['button_text'] = $this->triggerConfig['button_text'] ?? null;
                $node['data']['webhook_url'] = $this->triggerConfig['webhook_url'] ?? null;
                $node['data']['source_id'] = $this->triggerConfig['source_id'] ?? null;
                $node['data']['headline'] = $this->triggerConfig['headline'] ?? null;
                $node['data']['source_url'] = $this->triggerConfig['source_url'] ?? null;
                $node['data']['tag_name'] = $this->triggerConfig['tag_name'] ?? null;
                $node['data']['pipeline_id'] = $this->triggerConfig['pipeline_id'] ?? null;
                $node['data']['stage_id'] = $this->triggerConfig['stage_id'] ?? null;
                $node['data']['status'] = $this->triggerConfig['status'] ?? null;
                $node['data']['threshold'] = $this->triggerConfig['threshold'] ?? null;
                $node['data']['template_id'] = $this->triggerConfig['template_id'] ?? null;
                $node['data']['campaign_id'] = $this->triggerConfig['campaign_id'] ?? null;
                $node['data']['days'] = $this->triggerConfig['days'] ?? null;
                $node['data']['days_before'] = $this->triggerConfig['days_before'] ?? null;
                $node['data']['field_name'] = $this->triggerConfig['field_name'] ?? null;
                $node['data']['field_value'] = $this->triggerConfig['field_value'] ?? null;
            }
        }

        if (! $this->selectedNodeId) {
            return;
        }

        foreach ($this->nodes as &$node) {
            if ($node['id'] === $this->selectedNodeId) {
                $type = $node['type'];

                if ($type !== 'trigger') {
                    $node['data']['label'] = $this->nodeLabel ?: ($node['data']['label'] ?? ucfirst($type));
                }
                $node['data']['tag'] = $this->nodeTag;

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
                    $buttons = [];
                    foreach ($this->nodeOptions as $opt) {
                        $id = $opt['id'] ?? uniqid('btn-');
                        $buttons[] = ['id' => $id, 'title' => $opt['label']];
                    }
                    $node['data']['buttons'] = $buttons;
                } elseif ($type === 'interactive_list') {
                    $node['data']['text'] = $this->nodeText;
                    $node['data']['button_text'] = $this->nodeButtonText;
                    $rows = [];
                    foreach ($this->nodeOptions as $opt) {
                        $id = $opt['id'] ?? uniqid('row-');
                        $rows[] = ['id' => $id, 'title' => $opt['label'], 'description' => ''];
                    }
                    $node['data']['sections'] = [['title' => 'Options', 'rows' => $rows]];
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
                } elseif ($type === 'send_flow') {
                    $node['data']['flow_id'] = $this->nodeSaveTo;
                    $node['data']['text'] = $this->nodeText;
                } elseif ($type === 'crm_sync') {
                    $node['data']['provider'] = $this->nodeProvider;
                    $node['data']['action'] = $this->nodeAction;
                } elseif ($type === 'location_request') {
                    $node['data']['text'] = $this->nodeText;
                } elseif ($type === 'contact') {
                    $node['data']['contacts'] = collect($this->nodeContacts)->map(function ($c) {
                        return ['name' => ['formatted_name' => $c['name']], 'phones' => [['phone' => $c['phone']]]];
                    })->toArray();
                } elseif ($type === 'carousel') {
                    $node['data']['cards'] = $this->nodeCards;
                } elseif ($type === 'ab_split') {
                    $node['data']['ratio'] = $this->nodeRatio;
                } elseif ($type === 'send_email') {
                    $node['data']['template_name'] = $this->nodeLabel;
                    $node['data']['subject'] = $this->nodeTag;
                    $node['data']['body'] = $this->nodeText;
                } elseif ($type === 'create_deal') {
                    $node['data']['pipeline_id'] = $this->nodeProvider;
                    $node['data']['title'] = $this->nodeLabel;
                } elseif ($type === 'assign_to_agent') {
                    $node['data']['user_id'] = $this->nodeProvider;
                } elseif ($type === 'create_crm_task') {
                    $node['data']['title'] = $this->nodeLabel;
                    $node['data']['due_days'] = $this->nodeHours;
                    $node['data']['priority'] = $this->nodeTag;
                } elseif ($type === 'loop_over_items') {
                    $node['data']['items_path'] = $this->nodeLabel;
                } elseif ($type === 'set_variable') {
                    $node['data']['key'] = $this->nodeSaveTo;
                    $node['data']['operation'] = $this->nodeAction;
                    $node['data']['value'] = $this->nodeText;
                } elseif ($type === 'wait_until') {
                    $node['data']['value'] = $this->nodeDelayValue;
                    $node['data']['time_unit'] = $this->nodeDelayUnit;
                } elseif ($type === 'rate_limit_gate') {
                    $node['data']['window_hours'] = $this->nodeHours;
                } elseif ($type === 'sub_flow') {
                    $node['data']['target_flow_id'] = $this->nodeProvider;
                } elseif ($type === 'tag_contact') {
                    $node['data']['action'] = $this->nodeAction;
                    $node['data']['tag'] = $this->nodeTag;
                } elseif ($type === 'google_sheets') {
                    $node['data']['spreadsheet_id'] = $this->nodeUrl;
                    $node['data']['sheet_name'] = $this->nodeTag;
                    $node['data']['action'] = $this->nodeAction;
                } elseif ($type === 'handover') {
                    $node['data']['dept'] = $this->nodeProvider;
                } elseif ($type === 'stop_flow') {
                    $node['data']['label'] = 'End Flow';
                } elseif ($type === 'split_by_condition') {
                    $node['data']['rules'] = $this->nodeOptions;
                }
                break;
            }
        }
    }
}
