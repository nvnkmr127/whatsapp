<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Setting;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;

class AutomationValidationService
{
    /**
     * Validate an automation flow.
     */
    public function validate(Automation $automation): array
    {
        $flow = $automation->flow_data ?? [];
        $nodes = $flow['nodes'] ?? [];
        $edges = $flow['edges'] ?? [];

        $results = [
            'is_activatable' => true,
            'critical_errors' => 0,
            'warnings' => 0,
            'issues' => []
        ];

        // 1. Structural Validations
        if (empty($nodes)) {
            $this->addIssue($results, 'error', 'Automation has no nodes.');
        }

        $triggerNodes = collect($nodes)->where('type', 'trigger');
        if ($triggerNodes->isEmpty()) {
            $this->addIssue($results, 'error', 'Missing Start Trigger. Every flow needs a starting point.');
        }

        // 2. Loop Detection & Orphan Detection
        $nodeMap = collect($nodes)->keyBy('id');
        $adj = [];
        foreach ($edges as $edge) {
            $adj[$edge['source']][] = $edge['target'];
        }

        $visited = [];
        $stack = [];

        foreach ($triggerNodes as $trigger) {
            $this->detectCycles($trigger['id'], $adj, $visited, $stack, $results);
        }

        // Detect unreachable nodes (not reachable from any trigger)
        $reachable = [];
        foreach ($triggerNodes as $trigger) {
            $this->findReachable($trigger['id'], $adj, $reachable);
        }

        foreach ($nodes as $node) {
            if (!in_array($node['id'], $reachable)) {
                $this->addIssue($results, 'warning', "Node is unreachable from any trigger. This logic will never execute.", $node['id']);
            }
        }

        // 3. Node-Specific Content Validation
        foreach ($nodes as $node) {
            $this->validateNode($node, $automation->team_id, $results);

            // 4. Edge Connectivity Check for this node
            $isTerminalNode = in_array($node['type'], ['handover', 'stop']);
            $hasOutgoingEdges = collect($edges)->contains('source', $node['id']);

            if (!$isTerminalNode && !$hasOutgoingEdges && $node['type'] !== 'trigger') {
                $this->addIssue($results, 'warning', "Flow stops here. Add an outgoing path if this isn't the end.", $node['id']);
            }
        }

        return $results;
    }

    protected function detectCycles($u, $adj, &$visited, &$stack, &$results)
    {
        $visited[$u] = true;
        $stack[$u] = true;

        if (isset($adj[$u])) {
            foreach ($adj[$u] as $v) {
                if (!isset($visited[$v])) {
                    $this->detectCycles($v, $adj, $visited, $stack, $results);
                } elseif (isset($stack[$v])) {
                    $this->addIssue($results, 'error', "Circular loop detected. This will cause an infinite loop.", $u);
                }
            }
        }

        unset($stack[$u]);
    }

    protected function findReachable($u, $adj, &$reachable)
    {
        if (in_array($u, $reachable))
            return;
        $reachable[] = $u;
        if (isset($adj[$u])) {
            foreach ($adj[$u] as $v) {
                $this->findReachable($v, $adj, $reachable);
            }
        }
    }

    protected function validateNode(array $node, $teamId, array &$results)
    {
        $data = $node['data'] ?? [];

        switch ($node['type']) {
            case 'text':
            case 'message':
                if (empty($data['text'])) {
                    $this->addIssue($results, 'error', 'Text node is empty. Please add a message.', $node['id'], 'nodeText');
                }
                break;

            case 'template':
                $templateName = $data['template_name'] ?? null;
                if (!$templateName) {
                    $this->addIssue($results, 'error', 'No template selected.', $node['id'], 'nodeText');
                } else {
                    $exists = WhatsappTemplate::where('team_id', $teamId)
                        ->where('name', $templateName)
                        ->where('status', 'APPROVED')
                        ->exists();
                    if (!$exists) {
                        $this->addIssue($results, 'error', "Template '{$templateName}' is not approved or was deleted.", $node['id'], 'nodeText');
                    }
                }
                break;

            case 'openai':
                $apiKey = Setting::where('key', "ai_openai_api_key_$teamId")->value('value');
                if (!$apiKey) {
                    $this->addIssue($results, 'error', 'OpenAI API Key is missing in Team Settings.', $node['id']);
                }
                if (empty($data['prompt'])) {
                    $this->addIssue($results, 'warning', 'AI Prompt is empty. The bot will have no instructions.', $node['id'], 'nodeText');
                }
                break;

            case 'webhook':
            case 'api_request':
                if (empty($data['url'])) {
                    $this->addIssue($results, 'error', 'API URL is required.', $node['id'], 'nodeUrl');
                } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                    $this->addIssue($results, 'warning', 'API URL format looks invalid.', $node['id'], 'nodeUrl');
                }
                break;

            case 'delay':
                $min = (int) ($data['minutes'] ?? 0);
                $hrs = (int) ($data['hours'] ?? 0);
                if (($min + $hrs) <= 0) {
                    $this->addIssue($results, 'warning', 'Delay is set to 0. It will execute instantly.', $node['id'], 'nodeDelayValue');
                }
                break;

            case 'interactive_button':
                if (empty($data['buttons'])) {
                    $this->addIssue($results, 'error', 'Buttons are missing.', $node['id'], 'nodeOptions');
                }
                break;

            case 'handover':
                $hasAgents = \Illuminate\Support\Facades\DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('receives_tickets', true)
                    ->exists();
                if (!$hasAgents) {
                    $this->addIssue($results, 'error', 'No agents are configured to receive tickets. Handover will fail.', $node['id']);
                }
                break;
        }
    }

    protected function addIssue(array &$results, $level, $message, $nodeId = null, $field = null)
    {
        $results['issues'][] = [
            'level' => $level,
            'message' => $message,
            'node_id' => $nodeId,
            'field' => $field
        ];

        if ($level === 'error') {
            $results['critical_errors']++;
            $results['is_activatable'] = false;
        } else {
            $results['warnings']++;
        }
    }
}
