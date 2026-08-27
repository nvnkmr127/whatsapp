<?php

namespace App\Livewire\Automations\Traits;

use App\Models\Contact;

trait HasTestMode
{
    public bool $showTestModal = false;

    public string $testContactSearch = '';

    public ?int $testContactId = null;

    public array $testContacts = [];

    public array $testResult = [];

    public bool $testRunning = false;

    public function openTestModal(): void
    {
        $this->showTestModal = true;
        $this->testResult = [];
        $this->testContactId = null;
        $this->testContactSearch = '';
        $this->testContacts = [];
    }

    public function updatedTestContactSearch($value): void
    {
        if (strlen($value) < 2) {
            $this->testContacts = [];

            return;
        }

        $teamId = \Illuminate\Support\Facades\Auth::user()->currentTeam->id;
        $this->testContacts = Contact::where('team_id', $teamId)
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('phone_number', 'like', "%{$value}%");
            })
            ->limit(8)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'phone_number' => $c->phone_number])
            ->toArray();
    }

    public function selectTestContact(int $id): void
    {
        $contact = Contact::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->find($id);
        if (! $contact) {
            return;
        }
        $this->testContactId = $contact->id;
        $this->testContactSearch = "{$contact->name} ({$contact->phone_number})";
        $this->testContacts = [];
    }

    public function runTest(): void
    {
        if (! $this->testContactId) {
            session()->flash('error', 'Please select a contact to simulate against.');

            return;
        }

        $contact = Contact::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->find($this->testContactId);
        if (! $contact) {
            return;
        }

        $this->testRunning = true;
        $this->testResult = [];

        $flow = ['nodes' => $this->nodes, 'edges' => $this->edges];
        $variables = [
            'name' => $contact->name,
            'phone_number' => $contact->phone_number,
            'email' => $contact->email ?? '',
            'now' => now()->format('d M Y'),
            'time' => now()->format('H:i'),
        ];

        // Walk the flow and simulate execution
        $currentNodeId = null;
        foreach ($this->nodes as $node) {
            if ($node['type'] === 'trigger') {
                $currentNodeId = $node['id'];
                break;
            }
        }

        $steps = [];
        $maxSteps = 30;
        $visited = [];

        while ($currentNodeId && $maxSteps-- > 0) {
            if (in_array($currentNodeId, $visited)) {
                $steps[] = ['type' => 'loop', 'message' => '⚠️ Loop detected — stopping simulation.', 'node_id' => $currentNodeId];
                break;
            }
            $visited[] = $currentNodeId;

            $node = collect($this->nodes)->firstWhere('id', $currentNodeId);
            if (! $node) {
                break;
            }

            $data = $node['data'] ?? [];
            $type = $node['type'];
            $label = $data['label'] ?? $type;
            $text = $data['text'] ?? $data['question'] ?? $data['template_name'] ?? '';

            // Resolve variables in text
            $resolved = preg_replace_callback('/\{\{(\w[\w.]*)\}\}/', function ($m) use ($variables) {
                return $variables[$m[1]] ?? $m[0];
            }, $text);

            $step = [
                'node_id' => $currentNodeId,
                'type' => $type,
                'label' => $label,
                'icon' => $this->getNodeIcon($type),
                'color' => $this->getNodeColor($type),
                'status' => 'executed',
                'message' => null,
                'resolved' => $resolved,
            ];

            switch ($type) {
                case 'trigger':
                    $step['message'] = 'Flow starts here.';
                    break;
                case 'text':
                case 'interactive_button':
                case 'interactive_list':
                    $step['message'] = '📤 Would send: "'.mb_substr($resolved, 0, 120).(mb_strlen($resolved) > 120 ? '…' : '').'"';
                    break;
                case 'delay':
                case 'wait_until':
                    $step['message'] = '⏳ Would pause for '.($data['value'] ?? 0).' '.($data['time_unit'] ?? 'seconds').'.';
                    $step['status'] = 'wait';
                    break;
                case 'user_input':
                    $step['message'] = '💬 Would wait for reply and save to $'.($data['variable'] ?? 'response').'.';
                    $step['status'] = 'wait';
                    break;
                case 'condition':
                case 'split_by_condition':
                    $rules = $data['rules'] ?? [];
                    $step['message'] = '🔀 Evaluates '.count($rules).' branch rule(s). Taking default/first branch in preview.';
                    break;
                case 'set_variable':
                    $key = $data['key'] ?? $data['variable'] ?? '?';
                    $val = $data['value'] ?? '?';
                    $variables[$key] = $val;
                    $step['message'] = "📝 Sets \${$key} = \"{$val}\"";
                    break;
                case 'openai':
                    $step['message'] = '🤖 Would call AI model ('.($data['model'] ?? 'gpt-4o').'). Saving to $'.($data['save_to'] ?? 'ai_response').'.';
                    break;
                case 'send_email':
                    $step['message'] = '📧 Would send email: '.($data['subject'] ?? 'No Subject');
                    break;
                case 'webhook':
                case 'api_request':
                    $step['message'] = '🌐 Would call: '.($data['method'] ?? 'GET').' '.($data['url'] ?? '');
                    break;
                case 'tag_contact':
                    $action = ($data['action'] ?? 'add') === 'remove' ? 'Remove' : 'Add';
                    $step['message'] = '🏷 '.$action.' tag: "'.($data['tag'] ?? '').'"';
                    break;
                case 'handover':
                    $step['message'] = '👤 Would hand off to human agent. Flow ends.';
                    $step['status'] = 'terminal';
                    break;
                case 'stop_flow':
                    $step['message'] = '🛑 Flow ends here.';
                    $step['status'] = 'terminal';
                    break;
                case 'note':
                    $step['message'] = '📌 Note (no action executed).';
                    $step['status'] = 'skipped';
                    break;
                default:
                    $step['message'] = '▶ Node executed.';
            }

            $steps[] = $step;

            if (in_array($type, ['handover', 'stop_flow'])) {
                break;
            }
            if ($type === 'user_input') {
                break;
            }

            // Find next node
            $edges = collect($this->edges)->where('source', $currentNodeId);
            $nextEdge = $edges->first(fn ($e) => empty($e['condition'])) ?? $edges->first();
            $currentNodeId = $nextEdge['target'] ?? null;
        }

        $this->testResult = [
            'contact_name' => $contact->name,
            'contact_phone' => $contact->phone_number,
            'steps' => $steps,
            'variables' => $variables,
        ];
        $this->testRunning = false;
    }

    private function getNodeIcon(string $type): string
    {
        return match ($type) {
            'trigger' => 'M13 10V3L4 14h7v7l9-11h-7z',
            'text', 'message' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            'delay', 'wait_until' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'condition' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            'openai' => 'M13 10V3L4 14h7v7l9-11h-7z',
            'handover' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'stop_flow' => 'M6 18L18 6M6 6l12 12',
            default => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    private function getNodeColor(string $type): string
    {
        return match ($type) {
            'trigger' => 'text-amber-500 bg-amber-50',
            'text', 'message' => 'text-blue-500 bg-blue-50',
            'delay', 'wait_until' => 'text-slate-500 bg-slate-50',
            'condition','split_by_condition' => 'text-orange-500 bg-orange-50',
            'openai' => 'text-emerald-500 bg-emerald-50',
            'handover' => 'text-indigo-500 bg-indigo-50',
            'stop_flow' => 'text-rose-500 bg-rose-50',
            default => 'text-wa-teal bg-wa-teal/10',
        };
    }
}
