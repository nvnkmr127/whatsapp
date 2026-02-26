<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\AutomationStepLedger;
use App\Models\Contact;
use App\Jobs\ExecuteAutomationNodeJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AutomationService
{
    protected $whatsapp;
    protected $handoff;
    protected $healthMonitor;
    protected $policyService;
    protected const MAX_STEPS = 50;

    public function __construct(WhatsAppService $whatsapp, WhatsAppHealthMonitor $healthMonitor, PolicyService $policyService)
    {
        $this->whatsapp = $whatsapp;
        $this->whatsapp->isBot = true;
        $this->handoff = new BotHandoffService();
        $this->healthMonitor = $healthMonitor;
        $this->policyService = $policyService;
    }

    public function setWhatsAppService(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
        $this->whatsapp->isBot = true;
    }

    /**
     * Check if an automation should be triggered.
     */
    public function checkTriggers(Contact $contact, $messageContent)
    {
        Log::debug("Automation CheckTriggers: Starting for contact {$contact->id}", [
            'content' => $messageContent,
            'should_process' => $this->handoff->shouldProcess($contact)
        ]);

        if (!$this->handoff->shouldProcess($contact))
            return false;

        $messageContent = strtolower(trim($messageContent));
        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'keyword')
            ->get();

        Log::debug("Automation CheckTriggers: Found " . $automations->count() . " active keyword automations for team {$contact->team_id}");

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            $keywords = $automation->trigger_config['keywords'] ?? [];
            foreach ($keywords as $keyword) {
                $trimmedKeyword = trim($keyword);
                if ($trimmedKeyword !== "" && str_contains($messageContent, strtolower($trimmedKeyword))) {
                    Log::debug("Automation CheckTriggers: Match found for automation #{$automation->id} on keyword '$trimmedKeyword'");
                    $this->start($automation, $contact);
                    return true;
                }
            }
        }
        return false;
    }

    public function checkSpecialTriggers(Contact $contact, $type)
    {
        if (!$this->handoff->shouldProcess($contact))
            return false;

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', $type)
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            // Enforcement Hook: Logic for commerce-related automations
            if ($type === 'order_received') {
                $readinessService = app(\App\Services\CommerceReadinessService::class);
                if (!$readinessService->canPerformAction($contact->team, 'automation')) {
                    Log::warning("Automation trigger [order_received] blocked for team {$contact->team->id} due to commerce readiness failure.");
                    return false;
                }
            }
            $this->start($automation, $contact);
            return true;
        }
        return false;
    }

    public function checkTemplateTriggers(Contact $contact, $buttonText)
    {
        if (!$this->handoff->shouldProcess($contact))
            return false;

        $buttonText = strtolower(trim($buttonText));
        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'template_response')
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            $config = $automation->trigger_config ?? [];
            $matchText = strtolower(trim($config['button_text'] ?? ''));

            if ($buttonText === $matchText && !empty($matchText)) {
                $this->start($automation, $contact);
                return true;
            }
        }
        return false;
    }

    public function checkFlowTriggers(Contact $contact, $message)
    {
        if (!$this->handoff->shouldProcess($contact))
            return false;

        // Metadata is cast to array in the Message model
        $metadata = $message->metadata ?? [];
        $interactive = $metadata['interactive'] ?? [];
        if (($interactive['type'] ?? '') !== 'nfm_reply')
            return false;

        $responseJson = json_decode($interactive['nfm_reply']['response_json'] ?? '{}', true);
        $flowToken = $responseJson['flow_token'] ?? null;

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'flow_completion')
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            // Match against flow_token (assuming flow_token is set to flow_id or unique string)
            $targetToken = $automation->trigger_config['flow_token'] ?? $automation->trigger_config['flow_id'] ?? null;

            if ($targetToken && (string) $flowToken === (string) $targetToken) {
                // Pass flow data to the automation run state?
                // We'll leave that for a more advanced "Start" implementation, 
                // but at least we trigger it.
                $this->start($automation, $contact);
                return true;
            }
        }
        return false;
    }

    public function checkStatusTriggers(Contact $contact, $templateName, $status)
    {
        if ($status !== 'delivered')
            return false;

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'template_delivered')
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            $config = $automation->trigger_config ?? [];
            if (($config['template_name'] ?? null) === $templateName) {
                $this->start($automation, $contact);
                return true;
            }
        }
        return false;
    }

    protected function isAgentAssigned(Contact $contact): bool
    {
        return (bool) $contact->assigned_to;
    }

    /**
     * Start a new automation flow using the deterministic engine.
     */
    public function start(Automation $automation, Contact $contact, array $initialVariables = [])
    {
        $lock = Cache::lock("automation_start_{$contact->id}", 10);

        try {
            if (!$lock->get()) {
                Log::debug("Automation Start: Failed to acquire lock for contact {$contact->id}");
                return;
            }

            // 0. Billing Enforcement
            if (!$automation->team->canAccess('automations')) {
                Log::warning("Automation #{$automation->id} skipped: Feature [automations] not accessible for team {$automation->team_id}.");
                return;
            }

            if (!$automation->team->canAccess('send_message')) {
                Log::warning("Automation #{$automation->id} skipped: Message limit reached for team {$automation->team_id}.");
                return;
            }

            // Health Check Enforcement
            $health = $this->healthMonitor->checkHealth($automation->team);
            if ($health['status'] === 'restricted' || ($health['quality']['score'] ?? 100) <= 35) {
                Log::warning("Automation #{$automation->id} blocked: Account Restricted or Poor Quality (RED).", ['health' => $health]);
                return;
            }

            // Preflight Check (moved up before interruption)

            // Preflight Check
            $validation = $automation->validate();
            if (!$validation['is_activatable']) {
                Log::error("Attempted to start invalid automation #{$automation->id}: " . json_encode($validation['issues']));
                return;
            }

            $flowData = $automation->flow_data;
            $startNodeId = $this->findStartNode($flowData);

            if (!$startNodeId) {
                Log::error("Automation {$automation->id} has no start node.");
                return;
            }

            // Interrupt existing runs NOW, just before we commit to the new one
            AutomationRun::where('contact_id', $contact->id)
                ->whereIn('status', ['active', 'waiting_input', 'paused'])
                ->update(['status' => 'interrupted']);

            Log::debug("Automation #{$automation->id} starting for contact {$contact->id}", [
                'start_node' => $startNodeId,
                'run_id' => $run->id ?? 'pending'
            ]);

            $run = AutomationRun::create([
                'automation_id' => $automation->id,
                'contact_id' => $contact->id,
                'status' => 'active',
                'version' => 1,
                'step_count' => 0,
                'state_data' => ['current_node_id' => $startNodeId, 'variables' => $initialVariables],
                'execution_history' => [['node_id' => $startNodeId, 'timestamp' => now()->toDateTimeString(), 'event' => 'started']]
            ]);

            // Process Trigger Tags
            $triggerConfig = $automation->trigger_config ?? [];
            if (!empty($triggerConfig['add_tags'])) {
                foreach ($triggerConfig['add_tags'] as $tagName) {
                    $tag = \App\Models\ContactTag::firstOrCreate(['team_id' => $automation->team_id, 'name' => $tagName]);
                    $contact->tags()->syncWithoutDetaching([$tag->id]);
                }
            }
            if (!empty($triggerConfig['remove_tags'])) {
                foreach ($triggerConfig['remove_tags'] as $tagName) {
                    $tag = \App\Models\ContactTag::where('team_id', $automation->team_id)->where('name', $tagName)->first();
                    if ($tag) {
                        $contact->tags()->detach($tag->id);
                    }
                }
            }

            Log::debug("Automation #{$automation->id}: Run created with ID {$run->id}. Dispatching first node: {$startNodeId}");

            // Track Funnel Event
            \App\Models\CustomerEvent::create([
                'team_id' => $automation->team_id,
                'contact_id' => $contact->id,
                'event_type' => 'flow_started',
                'event_data' => [
                    'automation_id' => $automation->id,
                    'automation_name' => $automation->name,
                    'attributed_campaign_id' => \Illuminate\Support\Facades\Cache::get("last_campaign:contact:{$contact->phone_number}")
                ]
            ]);

            // Dispatch first node
            ExecuteAutomationNodeJob::dispatchSync($run->id, $startNodeId);
            Log::info("Automation #{$automation->id} successfully started for contact {$contact->id}. Run ID: {$run->id}");

        } catch (\Exception $e) {
            Log::error("Automation #{$automation->id} start FAILED for contact {$contact->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Entry point for the Background Job.
     */
    public function executeNodeSync(AutomationRun $run)
    {
        $nodeId = $run->state_data['current_node_id'];
        $flowData = $run->automation->flow_data;
        $node = $this->getNodeById($flowData, $nodeId);

        if (!$node) {
            Log::warning("AutomationRun #{$run->id}: Node {$nodeId} not found in flow data. Completing run.");
            $run->update(['status' => 'completed']);
            return;
        }

        Log::info("AutomationRun #{$run->id}: Executing node {$nodeId} (type: {$node['type']})");

        // Process Node-Level Stage Tagging
        $this->handleNodeTagging($run, $node);

        // Logic Entry
        try {
            $this->logHistory($run, $nodeId, 'executing');

            $workPerformed = $this->performNodeWork($run, $node);

            // Commit success to ledger
            AutomationStepLedger::updateOrCreate(
                ['execution_key' => "{$run->id}_{$nodeId}"],
                [
                    'automation_run_id' => $run->id,
                    'node_id' => $nodeId,
                    'status' => 'success',
                    'output_state' => $run->state_data
                ]
            );

            // Move or Wait
            if ($workPerformed === 'wait') {
                // Wait for input (status updated inside performNodeWork)
            } elseif ($workPerformed === 'pause') {
                // Delay node (status updated inside performNodeWork)
            } else {
                $this->moveToNextNode($run);
            }

        } catch (\Exception $e) {
            $this->failRun($run, $e->getMessage());
            throw $e;
        }
    }

    protected function performNodeWork(AutomationRun $run, array $node): string
    {
        $this->whatsapp->setTeam($run->automation->team);

        // 1. Handle Per-Node Delay (Sliders in the builder)
        $nodeDelaySeconds = ((int) ($node['data']['delay_hours'] ?? 0) * 3600) +
            ((int) ($node['data']['delay_minutes'] ?? 0) * 60) +
            (int) ($node['data']['delay_seconds'] ?? 0);

        // Ledger check for per-node delay to prevent double-waiting on retry
        $waitLedgerKey = "{$run->id}_{$node['id']}_wait";
        $hasWaited = AutomationStepLedger::where('execution_key', $waitLedgerKey)->exists();

        if ($nodeDelaySeconds > 0 && !$hasWaited) {
            $resumeAt = now()->addSeconds($nodeDelaySeconds);
            $run->update(['status' => 'paused', 'resume_at' => $resumeAt]);

            AutomationStepLedger::create([
                'execution_key' => $waitLedgerKey,
                'automation_run_id' => $run->id,
                'node_id' => $node['id'],
                'status' => 'success'
            ]);

            if ($nodeDelaySeconds <= 900) { // 15 min precision cutoff
                ExecuteAutomationNodeJob::dispatch($run->id, $node['id'])
                    ->onQueue('messages')
                    ->delay($resumeAt);
            }
            return 'pause';
        }

        // 2. Main Node Logic
        Log::debug("AutomationRun #{$run->id}: Performing node work for {$node['id']} (type: {$node['type']})");
        switch ($node['type']) {
            case 'text':
            case 'message':
                // Policy Check: 24h Window
                if (!$this->policyService->canSendFreeMessage($run->contact)) {
                    $this->failRun($run, "Validation Failed: Cannot send text message outside 24-hour window.");
                    return 'pause'; // Stop execution
                }

                $this->whatsapp->sendText($run->contact->phone_number, $node['data']['text'] ?? '');
                return 'continue';

            case 'image':
            case 'video':
            case 'audio':
            case 'file':
                $url = $node['data']['url'] ?? null;
                if ($url) {
                    // Ensure URL is absolute
                    if (str_starts_with($url, '/')) {
                        $url = url($url);
                    }
                    $this->whatsapp->sendMedia($run->contact->phone_number, $node['type'], $url, $node['data']['caption'] ?? '');
                }
                return 'continue';

            case 'template':
                $templateName = $node['data']['template_name'] ?? null;
                if ($templateName) {
                    $this->whatsapp->sendTemplate($run->contact->phone_number, $templateName, $node['data']['language'] ?? 'en_US');
                }
                return 'continue';

            case 'interactive_button':
            case 'interactive_list':
            case 'user_input':
            case 'question':
                // Send the question and mark as waiting
                $this->sendNodeMessage($run, $node);
                $run->update(['status' => 'waiting_input']);
                return 'wait';

            case 'delay':
                $value = (int) ($node['data']['value'] ?? 0);
                $unit = $node['data']['time_unit'] ?? 'minutes';
                $seconds = match ($unit) {
                    'seconds' => $value,
                    'minutes' => $value * 60,
                    'hours' => $value * 3600,
                    'days' => $value * 86400,
                    default => $value * 60,
                };

                if ($seconds > 0) {
                    $resumeAt = now()->addSeconds($seconds);
                    $run->update(['status' => 'paused', 'resume_at' => $resumeAt]);

                    if ($seconds <= 900) {
                        ExecuteAutomationNodeJob::dispatch($run->id, $run->state_data['current_node_id'])
                            ->onQueue('messages')
                            ->delay($resumeAt);
                    }
                    return 'pause';
                }
                return 'continue';

            case 'openai':
                $this->handleOpenAiNode($run, $node);
                return 'continue';

            case 'api_request':
            case 'webhook':
                $this->handleApiNode($run, $node);
                return 'continue';

            case 'handover':
                $this->handoff->pause($run->contact, 'handoff_node');
                (new AssignmentService)->assign($run->contact);
                $run->update(['status' => 'completed']);
                return 'pause'; // Terminates naturally

            case 'tag_contact':
            case 'create_ticket':
                $this->handleUtilityNode($run, $node);
                return 'continue';

            case 'trigger':
                return 'continue'; // Triggers are just entry points

            default:
                return 'continue';
        }
    }

    public function moveToNextNode(AutomationRun $run, $input = null)
    {
        if ($run->step_count >= self::MAX_STEPS) {
            $this->failRun($run, "Safe-guard limit reached: Max steps exceeded.");
            return;
        }

        $flowData = $run->automation->flow_data;
        $currentNodeId = $run->state_data['current_node_id'];
        $edges = array_filter($flowData['edges'], fn($e) => $e['source'] === $currentNodeId);

        Log::info("AutomationRun #{$run->id}: Moving from node {$currentNodeId}. Found " . count($edges) . " edges.");

        if (empty($edges)) {
            $run->update(['status' => 'completed']);
            return;
        }

        $nextNodeId = null;
        if ($input !== null) {
            foreach ($edges as $edge) {
                if (isset($edge['condition']) && strtolower(trim($input)) === strtolower(trim($edge['condition']))) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        }

        if (!$nextNodeId) {
            foreach ($edges as $edge) {
                if (empty($edge['condition'])) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        }

        $nextNodeId = $nextNodeId ?? ($edges[0]['target'] ?? null);

        if ($nextNodeId) {
            $state = $run->state_data;
            $state['current_node_id'] = $nextNodeId;
            $run->update(['state_data' => $state]);

            // DISPATCH JOB INSTEAD OF RECURSION
            Log::info("AutomationRun #{$run->id}: Dispatching SYNC job for next node: {$nextNodeId}");
            // Reset status to active so the next job can claim it
            $run->update(['status' => 'active', 'state_data' => $state]);
            ExecuteAutomationNodeJob::dispatchSync($run->id, $nextNodeId);
        } else {
            Log::info("AutomationRun #{$run->id}: Completed. No next node found.");
            $run->update(['status' => 'completed']);
        }
    }

    /**
     * Utility for manual resumption (Delayed Runs).
     * Uses atomic updates to prevent duplicate dispatching.
     */
    public function resumeScheduledRuns()
    {
        // 1. Recover crashed runs (status = executing but last_processed_at is too old)
        AutomationRun::where('status', 'executing')
            ->where('last_processed_at', '<', now()->subMinutes(10))
            ->update(['status' => 'active']);

        // 2. Pick up due pauses
        // We use an update-first approach to "claim" the runs atomically
        $runIds = AutomationRun::where('status', 'paused')
            ->whereNotNull('resume_at')
            ->where('resume_at', '<=', now())
            ->limit(100)
            ->pluck('id');

        if ($runIds->isEmpty()) {
            return;
        }

        // Atomically transition them to 'active' so other workers don't grab them
        AutomationRun::whereIn('id', $runIds)
            ->where('status', 'paused')
            ->update([
                'status' => 'active',
                'resume_at' => null,
                'last_processed_at' => now()
            ]);

        // Dispatch the jobs for the claimed runs
        $runs = AutomationRun::whereIn('id', $runIds)->get();
        foreach ($runs as $run) {
            ExecuteAutomationNodeJob::dispatchSync($run->id, $run->state_data['current_node_id']);
        }
    }

    /**
     * Handles user replies to waiting sessions.
     */
    public function handleReply(Contact $contact, $messageContent)
    {
        // 1. Check for Handoff status
        if (!$this->handoff->shouldProcess($contact)) {
            return false;
        }

        $run = AutomationRun::where('contact_id', $contact->id)
            ->where('status', 'waiting_input')
            ->first();

        if (!$run) {
            // Check for paused runs to cancel
            $pausedRun = AutomationRun::where('contact_id', $contact->id)->where('status', 'paused')->first();
            if ($pausedRun) {
                $pausedRun->update(['status' => 'interrupted', 'resume_at' => null]);
            }
            return false;
        }

        // Save variable if required by node
        $currentNode = $this->getNodeById($run->automation->flow_data, $run->state_data['current_node_id']);
        if ($currentNode && isset($currentNode['data']['variable'])) {
            $vars = $run->state_data['variables'];
            $vars[$currentNode['data']['variable']] = $messageContent;
            $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
        }

        $run->update(['status' => 'active']);
        $this->moveToNextNode($run, $messageContent);

        return true;
    }

    // --- Internal Helpers ---

    protected function handleOpenAiNode(AutomationRun $run, array $node)
    {
        if (!$run->automation->team->canAccess('ai')) {
            throw new \Exception("AI feature not available on your current plan.");
        }

        $teamId = $run->automation->team_id;
        $apiKey = \App\Models\Setting::where('key', "ai_openai_api_key_$teamId")->value('value');
        if (!$apiKey)
            throw new \Exception("OpenAI API Key missing");

        $useKb = $node['data']['use_knowledge_base'] ?? false;
        $context = "";

        if ($useKb) {
            $kbService = app(KnowledgeBaseService::class);
            $scopedSourceIds = $node['data']['kb_source_ids'] ?? null;
            $kbScope = $node['data']['kb_scope'] ?? 'all'; // all, selected

            if ($kbScope === 'selected' && empty($scopedSourceIds)) {
                Log::warning("OpenAI Node configured with 'selected' scope but no sources provided for team $teamId");
                throw new \Exception("Knowledge Base scope is restricted but no sources are selected.");
            }

            $sourceIdsToUse = ($kbScope === 'selected') ? $scopedSourceIds : null;

            if (!$kbService->isReady($teamId, $sourceIdsToUse)) {
                // Blocking AI usage when KB is not ready
                Log::warning("OpenAI Node blocked: Knowledge Base scope is not ready for team $teamId");
                throw new \Exception("The selected Knowledge Base sources are not ready for use.");
            }

            $context = $kbService->searchContext($teamId, $run->state_data['variables']['last_message'] ?? $node['data']['prompt'], $sourceIdsToUse);
        }

        $prompt = $node['data']['prompt'] ?? '';
        foreach ($run->state_data['variables'] as $k => $v) {
            $prompt = str_replace('{{' . $k . '}}', (string) $v, $prompt);
        }

        if ($context) {
            $strict = $node['data']['kb_strict'] ?? true;

            if ($strict) {
                $groundingRules = "
STRICT GROUNDING RULES:
1. Use ONLY the 'Business Context' provided below to answer.
2. If the answer is not in the context, respond exactly with: \"I'm sorry, I don't have information about that in my business knowledge base.\"
3. DO NOT speculate or use outside knowledge.
4. CITATIONS: At the end of your response, list the sources used as '[Source: Name]'.
";
                $prompt = "--- SYSTEM INSTRUCTIONS ---\n" . $groundingRules . "\n\n--- BUSINESS CONTEXT ---\n" . $context . "\n\n--- USER QUESTION ---\n" . $prompt;
            } else {
                $prompt = "--- BUSINESS CONTEXT (USE FOR GUIDANCE) ---\n" . $context . "\n\n--- USER QUESTION ---\n" . $prompt;
            }
        }

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');

            // Check for grounding failure (unanswered)
            if (str_contains($content, "I'm sorry, I don't have information about that in my business knowledge base.")) {
                app(KnowledgeBaseService::class)->logGap(
                    $run->automation->team_id,
                    $prompt, // The prompt includes the user question
                    'unanswered'
                );
            }

            $vars = $run->state_data['variables'];
            $vars[$node['data']['save_to'] ?? 'ai_response'] = $content;
            $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);

            // Record Usage for Billing
            \App\Models\ActivityLog::create([
                'team_id' => $teamId,
                'action' => 'ai_interaction',
                'description' => "AI Node executed in Automation #{$run->automation_id}",
                'subject_type' => get_class($run),
                'subject_id' => $run->id
            ]);
        }
    }

    protected function handleApiNode(AutomationRun $run, array $node)
    {
        $url = $node['data']['url'] ?? '';
        $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
        if ($response->successful() && isset($node['data']['save_to'])) {
            $vars = $run->state_data['variables'];
            $vars[$node['data']['save_to']] = $response->json();
            $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
        }
    }

    protected function handleUtilityNode($run, $node)
    {
        if ($node['type'] === 'tag_contact') {
            $tagName = $node['data']['tag'] ?? 'Lead';
            $tag = \App\Models\ContactTag::firstOrCreate(['team_id' => $run->automation->team_id, 'name' => $tagName]);
            $run->contact->tags()->syncWithoutDetaching([$tag->id]);
        }
    }

    protected function handleNodeTagging(AutomationRun $run, array $node)
    {
        $tagToAssign = $node['data']['tag'] ?? null;
        if (!$tagToAssign)
            return;

        $state = $run->state_data;
        $previousTag = $state['current_stage_tag'] ?? null;

        if ($previousTag === $tagToAssign)
            return;

        $teamId = $run->automation->team_id;
        $contact = $run->contact;

        DB::transaction(function () use ($run, $tagToAssign, $previousTag, &$state, $teamId, $contact) {
            // 1. Remove previous stage tag if it belongs to this flow run tracking
            if ($previousTag) {
                $pTag = \App\Models\ContactTag::where('team_id', $teamId)->where('name', $previousTag)->first();
                if ($pTag) {
                    $contact->tags()->detach($pTag->id);
                }
            }

            // 2. Assign new tag
            $tag = \App\Models\ContactTag::firstOrCreate([
                'team_id' => $teamId,
                'name' => $tagToAssign
            ]);

            $contact->tags()->syncWithoutDetaching([$tag->id]);

            // 3. Update state
            $state['current_stage_tag'] = $tagToAssign;
        });

        $run->update(['state_data' => $state]);
    }

    protected function sendNodeMessage($run, $node)
    {
        $text = $node['data']['text'] ?? $node['data']['question'] ?? '';
        if ($node['type'] === 'interactive_button') {
            $btns = [];
            foreach ($node['data']['buttons'] ?? [] as $b) {
                $btns[$b['id']] = $b['title'];
            }
            $this->whatsapp->sendInteractiveButtons($run->contact->phone_number, $text, $btns);
        } else {
            $this->whatsapp->sendText($run->contact->phone_number, $text);
        }
    }

    protected function logHistory(AutomationRun $run, $nodeId, $event)
    {
        $history = $run->execution_history ?? [];
        $history[] = ['node_id' => $nodeId, 'event' => $event, 'timestamp' => now()->toDateTimeString()];
        $run->update(['execution_history' => array_slice($history, -50)]);
    }

    protected function failRun(AutomationRun $run, $error)
    {
        Log::error("AutomationRun #{$run->id} FAILED: {$error}");
        $run->update(['status' => 'failed', 'error_message' => $error]);
    }

    protected function findStartNode($flowData)
    {
        if (!isset($flowData['nodes']) || !is_array($flowData['nodes'])) {
            return null;
        }

        foreach ($flowData['nodes'] as $n) {
            if (($n['type'] ?? '') === 'trigger')
                return $n['id'];
        }
        return null;
    }

    protected function getNodeById($flowData, $id)
    {
        if (!isset($flowData['nodes']) || !is_array($flowData['nodes'])) {
            return null;
        }

        foreach ($flowData['nodes'] as $n) {
            if (($n['id'] ?? '') === $id)
                return $n;
        }
        return null;
    }
}
