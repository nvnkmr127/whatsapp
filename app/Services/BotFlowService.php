<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Flow;
use App\Models\FlowSession;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class BotFlowService
{
    /**
     * Attempt to trigger a flow by keyword.
     */
    public function handleKeyword(Contact $contact, string $keyword)
    {
        // 1. Check if user already has active session?
        // If so, maybe keyword 'restart' or 'abort' kills it.
        // Or if keyword matches a NEW flow, do we switch?
        // Let's assume exact match keyword starts new flow.

        $flow = Flow::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_keyword', 'LIKE', $keyword) // SQLite is case-insensitive by default for ASCII
            ->first();

        if (!$flow) {
            // Check PHP side logic (strtoupper compare) if DB ILIKE not reliable
            return false;
        }

        return $this->startFlow($contact, $flow);
    }

    public function startFlow(Contact $contact, Flow $flow)
    {
        // Cancel existing sessions
        FlowSession::where('contact_id', $contact->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $session = FlowSession::create([
            'flow_id' => $flow->id,
            'contact_id' => $contact->id,
            'current_step_id' => $flow->nodes[0]['id'] ?? null, // Start node
            'state' => [],
            'status' => 'active'
        ]);

        $this->executeStep($session);
        return true;
    }

    public function processInput(Contact $contact, string $input)
    {
        $session = FlowSession::where('contact_id', $contact->id)
            ->where('status', 'active')
            ->first();

        if (!$session)
            return false;

        $flow = $session->flow;
        $currentNodeId = $session->current_step_id;
        $node = $this->findNode($flow, $currentNodeId);

        // Store Input (if strictly waiting for input)
        // Simplified: Store input against current node ID
        $state = $session->state ?? [];
        $state[$currentNodeId] = $input;
        $session->update(['state' => $state]);

        // Find Next
        $nextNodeId = $this->determineNextNode($flow, $currentNodeId);

        if ($nextNodeId) {
            $session->update(['current_step_id' => $nextNodeId]);
            $this->executeStep($session);
        } else {
            $session->update(['status' => 'completed']);
        }

        return true;
    }

    protected function executeStep(FlowSession $session)
    {
        $flow = $session->flow;
        $node = $this->findNode($flow, $session->current_step_id);

        if (!$node) {
            $session->update(['status' => 'failed']);
            return;
        }

        $type = $node['type'] ?? 'message';
        $content = $node['data']['content'] ?? '';

        // Execute Action
        $waService = new WhatsAppService();
        $waService->setTeam($flow->team); // Assuming Flow has Team

        try {
            $waService->sendText($session->contact->phone_number, $content);
        } catch (\Exception $e) {
            Log::error("Bot Execution Error: " . $e->getMessage());
        }

        // Decisions
        if ($type === 'message') {
            // Auto advance
            $nextNodeId = $this->determineNextNode($flow, $node['id']);
            if ($nextNodeId) {
                $session->update(['current_step_id' => $nextNodeId]);
                $this->executeStep($session); // Recurse
            } else {
                $session->update(['status' => 'completed']);
            }
        } elseif ($type === 'question') {
            // Stop and Wait
        } elseif ($type === 'handover') {
            // Mark flow as completed
            $session->update(['status' => 'completed']);
            // Update active conversation to 'open' (needs human)
            $conversation = $session->contact->activeConversation;
            if (!$conversation) {
                // If no active convo, resolve one? Or create new?
                // Usually we just mark it.
                // For now, if no convo, create one?
                $conversation = (new \App\Services\ConversationService())->ensureActiveConversation($session->contact);
            }
            // Logic: Handover means unassign bot, maybe assign to 'Support' queue?
            $conversation->update(['status' => 'open', 'assigned_to' => null]);
            // Maybe add a note?
            $conversation->internalNotes()->create([
                'content' => 'Bot: Handed over to human agent.',
                'user_id' => null // System
            ]);
        } elseif ($type === 'add_tag') {
            $tag = $node['data']['tag'] ?? null;
            if ($tag) {
                // Need ContactService or direct helper
                // Assuming Spatie Tags or similar. For now just ignore if no implementation.
                // $session->contact->attachTag($tag);
            }
            // Auto Advance
            $nextNodeId = $this->determineNextNode($flow, $node['id']);
            if ($nextNodeId) {
                $session->update(['current_step_id' => $nextNodeId]);
                $this->executeStep($session);
            } else {
                $session->update(['status' => 'completed']);
            }
        }
    }

    protected function findNode(Flow $flow, $nodeId)
    {
        $nodes = $flow->nodes ?? [];
        foreach ($nodes as $node) {
            if (($node['id'] ?? '') === $nodeId)
                return $node;
        }
        return null;
    }

    protected function determineNextNode($flow, $currentId)
    {
        $edges = $flow->edges ?? [];
        foreach ($edges as $edge) {
            if (($edge['source'] ?? '') === $currentId) {
                return $edge['target'] ?? null;
            }
        }
        return null;
    }
}
