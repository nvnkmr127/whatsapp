<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Message;
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
    protected $maxSteps;
    protected $nodeHandlers = [];

    public function __construct(WhatsAppService $whatsapp, WhatsAppHealthMonitor $healthMonitor, PolicyService $policyService)
    {
        $this->whatsapp = $whatsapp;
        $this->whatsapp->isBot = true;
        // BotHandoffService is currently instantiated directly? No, it's assigned below.
        $this->healthMonitor = $healthMonitor;
        $this->policyService = $policyService;
        $this->maxSteps = config('automation.max_steps', 50);
        $this->handoff = new BotHandoffService();

        // Initialize Node Handlers (Strategy Pattern)
        $this->nodeHandlers = [
            'text' => new \App\Core\Automations\NodeHandlers\MessageNodeHandler($whatsapp),
            'message' => new \App\Core\Automations\NodeHandlers\MessageNodeHandler($whatsapp),
            'question' => new \App\Core\Automations\NodeHandlers\QuestionNodeHandler($whatsapp),
            'user_input' => new \App\Core\Automations\NodeHandlers\QuestionNodeHandler($whatsapp),
        ];
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

        $messageContent = mb_strtolower(trim($messageContent));
        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->whereIn('trigger_type', ['keyword', 'multi'])
            ->get();

        Log::debug("Automation CheckTriggers: Found " . $automations->count() . " active keyword automations for team {$contact->team_id}");

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            $config = $automation->trigger_config ?? [];
            $keywords = $config['keywords'] ?? [];
            $isRegex = $config['is_regex'] ?? false;

            foreach ($keywords as $keyword) {
                if ($this->matchKeyword($keyword, $messageContent, $isRegex)) {
                    Log::debug("Automation CheckTriggers: Match found for automation #{$automation->id} on keyword '$keyword'");
                    $this->start($automation, $contact);
                    return true;
                }
            }

            // Handle Multi-Trigger keyword matching
            if ($automation->trigger_type === 'multi') {
                $subTriggers = $automation->trigger_config['triggers'] ?? [];
                foreach ($subTriggers as $st) {
                    if (($st['type'] ?? '') === 'keyword') {
                        $skisRegex = $st['is_regex'] ?? false;
                        foreach ($st['keywords'] ?? [] as $sk) {
                            if ($this->matchKeyword($sk, $messageContent, $skisRegex)) {
                                $this->start($automation, $contact);
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    protected function matchKeyword($keyword, $content, $isRegex): bool
    {
        $trimmedKeyword = trim($keyword);
        if ($trimmedKeyword === "") return false;

        if ($isRegex) {
            try {
                $pattern = $trimmedKeyword;
                if (!str_starts_with($pattern, '/') || !str_ends_with($pattern, '/')) {
                    $pattern = '/' . str_replace('/', '\/', $pattern) . '/i';
                }
                return (bool)preg_match($pattern, $content);
            } catch (\Exception $e) {
                return false;
            }
        }
        return str_contains($content, mb_strtolower($trimmedKeyword));
    }

    public function checkSpecialTriggers(Contact $contact, $type, array $variables = [])
    {
        Log::debug("Automation CheckSpecialTriggers: Type {$type} for contact {$contact->id}", [
            'variables' => $variables
        ]);

        if (!$this->handoff->shouldProcess($contact))
            return false;

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where(function($q) use ($type) {
                $q->where('trigger_type', $type)->orWhere('trigger_type', 'multi');
            })
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            
            $matched = ($automation->trigger_type === $type);
            
            if (!$matched && $automation->trigger_type === 'multi') {
                $subTriggers = $automation->trigger_config['triggers'] ?? [];
                foreach ($subTriggers as $st) {
                    if (($st['type'] ?? '') === $type) {
                        // Check sub-trigger specific conditions if any
                        if ($type === 'contact_field_updated') {
                            $targetField = $st['field_name'] ?? null;
                            if ($targetField && !array_key_exists($targetField, $variables)) {
                                continue;
                            }
                        }

                        if ($type === 'webhook_payload_received') {
                            $conditions = $st['conditions'] ?? [];
                            foreach ($conditions as $c) {
                                $vKey = $c['key'] ?? '';
                                $vOp = $c['operator'] ?? 'eq';
                                $vVal = $c['value'] ?? '';
                                if (!$this->evaluateOperator($variables[$vKey] ?? null, $vOp, $vVal)) {
                                    continue 2; // Matched trigger type but failed specific payload condition
                                }
                            }
                        }
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) continue;

            // Trigger specific pre-flight checks or filtering
            if ($type === 'order_received') {
                $readinessService = app(\App\Services\CommerceReadinessService::class);
                if (!$readinessService->canPerformAction($contact->team, 'automation')) {
                    Log::warning("Automation trigger [order_received] blocked for team {$contact->team->id} due to commerce readiness failure.");
                    return false;
                }
            }

            if ($type === 'inactivity') {
                $days = $automation->trigger_config['days'] ?? 7;
                $lastMsg = $contact->messages()->where('direction', 'inbound')->latest()->first();
                if ($lastMsg && $lastMsg->created_at->diffInDays(now()) < $days) {
                    continue;
                }
            }

            $this->start($automation, $contact, $variables);
            return true;
        }
        return false;
    }

    /**
     * Check deal-related triggers.
     */
    public function checkDealTriggers(\App\Models\Deal $deal, string $type, array $context = [])
    {
        $contact = $deal->contact;
        if (!$contact) {
            return false;
        }

        if (!$this->handoff->shouldProcess($contact)) {
            return false;
        }

        $automations = Automation::where('team_id', $deal->team_id)
            ->where('is_active', true)
            ->where('trigger_type', $type)
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            $config = $automation->trigger_config ?? [];

            // Stage filter for deal_stage_changed
            if ($type === 'deal_stage_changed') {
                $targetStageId = $config['stage_id'] ?? null;
                if ($targetStageId && (int)$deal->stage_id !== (int)$targetStageId) {
                    continue;
                }
            }

            // Prepare automation variables from deal and context
            $variables = array_merge([
                'deal_id' => $deal->id,
                'deal_title' => $deal->title,
                'deal_value' => $deal->value,
                'deal_status' => $deal->status,
                'deal_stage_id' => $deal->stage_id,
            ], $context);

            $this->start($automation, $contact, $variables);
            return true;
        }
        return false;
    }

    /**
     * Fire automations that trigger when a specific tag is added to a contact.
     * Called from ContactTag sync/attach observers.
     */
    public function checkTagAddedTriggers(Contact $contact, string $tagName, string $source = 'any'): bool
    {
        if (!$this->handoff->shouldProcess($contact)) {
            return false;
        }

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'tag_added')
            ->get();

        foreach ($automations as $automation) {
            $config = $automation->trigger_config ?? [];
            $targetTag = trim($config['tag_name'] ?? '');

            if (empty($targetTag) || strtolower($targetTag) !== strtolower($tagName)) {
                continue;
            }

            $allowedSource = $config['tag_source'] ?? 'any';
            if ($allowedSource !== 'any' && $allowedSource !== $source) {
                continue;
            }

            // Deduplication check
            if ($config['deduplicate'] ?? true) {
                $alreadyRan = AutomationRun::where('automation_id', $automation->id)
                    ->where('contact_id', $contact->id)
                    ->whereIn('status', ['completed', 'active', 'waiting_input'])
                    ->exists();
                if ($alreadyRan) {
                    Log::debug("Automation#{$automation->id} skipped (deduplicate): contact {$contact->id} already ran this flow.");
                    continue;
                }
            }

            $this->start($automation, $contact, ['trigger_tag' => $tagName, 'trigger_source' => $source]);
            return true;
        }
        return false;
    }

    /**
     * Fire automations that trigger from an inbound web-form webhook submission.
     * Called from UniversalWebhookListener when source matches 'form_submitted' automations.
     */
    public function checkFormSubmittedTriggers(Contact $contact, array $formData = []): bool
    {
        if (!$this->handoff->shouldProcess($contact)) {
            return false;
        }

        $formSource = $formData['source'] ?? '';

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'form_submitted')
            ->get();

        foreach ($automations as $automation) {
            $config      = $automation->trigger_config ?? [];
            $targetSource = trim($config['form_source'] ?? '');

            // If a source label is configured, it must match
            if (!empty($targetSource) && strtolower($targetSource) !== strtolower($formSource)) {
                continue;
            }

            // Pass all form fields as automation variables (prefixed with form_)
            $variables = [];
            foreach ($formData as $k => $v) {
                if (is_scalar($v)) {
                    $variables["form_{$k}"] = $v;
                }
            }

            $this->start($automation, $contact, $variables);
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

    public function checkReferralTriggers(Contact $contact, $referralData)
    {
        if (!$this->handoff->shouldProcess($contact))
            return false;

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'referral')
            ->get();

        foreach ($automations as $automation) {
            /** @var Automation $automation */
            $config = $automation->trigger_config ?? [];

            // 1. Source ID Match (Ad ID)
            $targetSourceId = $config['source_id'] ?? null;
            if ($targetSourceId && ($referralData['source_id'] ?? '') !== $targetSourceId) {
                continue;
            }

            // 2. Headline Match (Partial)
            $targetHeadline = $config['headline'] ?? null;
            if ($targetHeadline && !str_contains(strtolower($referralData['headline'] ?? ''), strtolower($targetHeadline))) {
                continue;
            }

            // 3. Source URL Match (Partial)
            $targetUrl = $config['source_url'] ?? null;
            if ($targetUrl && !str_contains(strtolower($referralData['source_url'] ?? ''), strtolower($targetUrl))) {
                continue;
            }

            $variables = [];
            foreach ($referralData as $key => $value) {
                if (is_scalar($value)) {
                    $variables["referral_$key"] = $value;
                }
            }
            $this->start($automation, $contact, $variables);
            return true;
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
            $healthThreshold = config('whatsapp.thresholds.health_quality_score', 35);
            if ($health['status'] === 'restricted' || ($health['quality']['score'] ?? 100) <= $healthThreshold) {
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
            ExecuteAutomationNodeJob::dispatch($run->id, $startNodeId);
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
        $run->increment('step_count');

        // Infinite Loop protection (recursion gate)
        if ($run->step_count > 50) {
            Log::error("AutomationRun #{$run->id}: Max step count (50) exceeded. Suspected infinite loop. Aborting.");
            $run->update(['status' => 'failed', 'error_message' => 'Recursion limit exceeded']);
            return;
        }

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
            // S4: Error / Fallback Path Handling
            Log::error("AutomationRun #{$run->id}: Node {$nodeId} execution error: " . $e->getMessage());
            
            $state = $run->state_data;
            $state['error_message'] = $e->getMessage();
            $state['last_error_node'] = $nodeId;
            $state['api_result'] = 'error'; // Unified error flag for conditions
            $run->update(['state_data' => $state]);

            // Attempt to move to 'error' edge if one exists
            if ($this->hasErrorEdge($run, $nodeId)) {
                $this->moveToNextNode($run, 'error');
            } else {
                $this->failRun($run, $e->getMessage());
                throw $e;
            }
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

            $precision = config('automation.dispatch_precision_seconds', 900);
        if ($nodeDelaySeconds <= $precision) { // precision cutoff
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
                $text = $this->resolveVariable($run, $node['data']['text'] ?? '');
                $trackingMessage = $this->createAutomationTrackingMessage(
                    $run,
                    'text',
                    $text,
                    ['node_id' => $node['id'] ?? null, 'node_type' => $node['type']]
                );
                $this->whatsapp->sendText($run->contact->phone_number, $text, $trackingMessage);
                return 'continue';

            case 'interactive_button':
            case 'interactive_list':
                $text = $this->resolveVariable($run, $node['data']['text'] ?? '');
                $options = [];
                if ($node['type'] === 'interactive_button') {
                    foreach ($node['data']['buttons'] ?? [] as $b) {
                        $options[$b['id']] = $this->resolveVariable($run, $b['title']);
                    }
                    $trackingMessage = $this->createAutomationTrackingMessage(
                        $run,
                        'interactive',
                        $text,
                        ['node_id' => $node['id'], 'node_type' => $node['type'], 'buttons' => $options]
                    );
                    $this->whatsapp->sendInteractiveButtons($run->contact->phone_number, $text, $options, $trackingMessage);
                } else {
                    $buttonText = $this->resolveVariable($run, $node['data']['button_text'] ?? 'View Options');
                    $sections = [];
                    foreach ($node['data']['sections'] ?? [] as $s) {
                        $rows = [];
                        foreach ($s['rows'] ?? [] as $r) {
                            $rows[] = [
                                'id' => $r['id'],
                                'title' => $this->resolveVariable($run, $r['title']),
                                'description' => $this->resolveVariable($run, $r['description'] ?? '')
                            ];
                        }
                        $sections[] = ['title' => $this->resolveVariable($run, $s['title'] ?? 'Options'), 'rows' => $rows];
                    }
                    $trackingMessage = $this->createAutomationTrackingMessage(
                        $run,
                        'interactive',
                        $text,
                        ['node_id' => $node['id'], 'node_type' => $node['type'], 'sections' => $sections]
                    );
                    $this->whatsapp->sendInteractiveList($run->contact->phone_number, $text, $buttonText, $sections, $trackingMessage);
                }
                return 'wait';

            case 'user_input':
                $text = $this->resolveVariable($run, $node['data']['question'] ?? '');
                $trackingMessage = $this->createAutomationTrackingMessage(
                    $run,
                    'text',
                    $text,
                    ['node_id' => $node['id'], 'node_type' => 'question']
                );
                $this->whatsapp->sendText($run->contact->phone_number, $text, $trackingMessage);
                $run->update(['status' => 'waiting_input']);
                return 'wait';

            case 'send_flow':
                $flowId = $node['data']['flow_id'] ?? null;
                $headline = $this->resolveVariable($run, $node['data']['headline'] ?? '');
                $body = $this->resolveVariable($run, $node['data']['body'] ?? '');
                $cta = $this->resolveVariable($run, $node['data']['cta'] ?? 'Open Form');
                if ($flowId) {
                    $this->whatsapp->sendFlow(
                        $run->contact->phone_number,
                        $flowId,
                        $headline,
                        $body,
                        $cta,
                        'published',
                        $node['data']['screen'] ?? null,
                        $run->state_data['variables'] ?? []
                    );
                    $run->update(['status' => 'waiting_input']);
                }
                return 'wait';

            case 'crm_sync':
                $provider = $node['data']['provider'] ?? 'internal';
                $action = $node['data']['action'] ?? 'update_lead';
                Log::info("AutomationRun #{$run->id}: Syncing with {$provider} ({$action})");
                
                if ($provider === 'internal') {
                    // Perform internal sync logic (e.g., ensure contact is marked as lead)
                    $run->contact->update(['type' => 'lead']);
                    if ($action === 'create_deal') {
                        \App\Models\Deal::firstOrCreate([
                            'contact_id' => $run->contact->id,
                            'team_id' => $run->contact->team_id,
                            'status' => 'open'
                        ], [
                            'title' => 'Deal from Automation: ' . ($run->automation->name ?? 'Default'),
                            'stage_id' => $node['data']['stage_id'] ?? null
                        ]);
                    }
                }

                if (class_exists(\App\Services\WorkflowEngine::class)) {
                    app(\App\Services\WorkflowEngine::class)->trigger('crm_sync_requested', $run->contact, array_merge([
                        'provider' => $provider,
                        'action' => $action,
                        'automation_id' => $run->automation_id,
                        'run_id' => $run->id
                    ], $run->state_data['variables'] ?? []));
                }
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
                    $trackingMessage = $this->createAutomationTrackingMessage(
                        $run,
                        $node['type'],
                        $node['data']['caption'] ?? null,
                        ['node_id' => $node['id'] ?? null, 'node_type' => $node['type']],
                        [
                            'media_type' => $node['type'],
                            'media_url' => $url,
                            'caption' => $node['data']['caption'] ?? null,
                        ]
                    );

                    $this->whatsapp->sendMedia($run->contact->phone_number, $node['type'], $url, $node['data']['caption'] ?? '', $trackingMessage);
                }
                return 'continue';

            case 'template':
                $templateName = $node['data']['template_name'] ?? null;
                if ($templateName) {
                    $trackingMessage = $this->createAutomationTrackingMessage(
                        $run,
                        'template',
                        $templateName,
                        ['node_id' => $node['id'] ?? null, 'node_type' => $node['type'], 'template_name' => $templateName]
                    );

                    $this->whatsapp->sendTemplate(
                        $run->contact->phone_number,
                        $templateName,
                        $node['data']['language'] ?? 'en_US',
                        [],
                        [],
                        [],
                        null,
                        $trackingMessage
                    );
                }
                return 'continue';

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

            case 'condition':
            case 'split_by_condition':
                $branches = $node['data']['branches'] ?? [];
                $result = 'default';
                
                if (empty($branches)) {
                    // Legacy single-condition support
                    $variable = $node['data']['variable'] ?? '';
                    $operator = $node['data']['operator'] ?? 'eq';
                    $value = $this->resolveVariable($run, $node['data']['value'] ?? '');
                    $actual = $run->state_data['variables'][$variable] ?? $run->contact->{$variable} ?? null;
                    
                    if ($this->evaluateOperator($actual, $operator, $value)) {
                        $result = 'true';
                    } else {
                        $result = 'false';
                    }
                } else {
                    // Multi-branch split_by_condition
                    foreach ($branches as $branch) {
                        $variable = $branch['variable'] ?? '';
                        $operator = $branch['operator'] ?? 'eq';
                        $value = $this->resolveVariable($run, $branch['value'] ?? '');
                        $actual = $run->state_data['variables'][$variable] ?? $run->contact->{$variable} ?? null;

                        if ($this->evaluateOperator($actual, $operator, $value)) {
                            $result = $branch['name'] ?? 'match';
                            break;
                        }
                    }
                }

                $state = $run->state_data;
                $state['condition_result'] = $result;
                $run->update(['state_data' => $state]);
                return 'continue';

            case 'send_email':
                $templateSlug = $node['data']['template_name'] ?? $node['data']['template_slug'] ?? null;
                $to = $this->resolveVariable($run, $node['data']['to'] ?? '{{email}}');
                if ($templateSlug && $to) {
                    app(\App\Services\Email\EmailDispatcher::class)->dispatchByTemplate($to, $templateSlug, [
                        'contact' => $run->contact,
                        'run' => $run,
                        'variables' => $run->state_data['variables'] ?? []
                    ]);
                }
                return 'continue';

            case 'send_internal_notification':
                $agentId = $node['data']['agent_id'] ?? null;
                $message = $this->resolveVariable($run, $node['data']['message'] ?? 'Notification from Automation');
                $channel = $node['data']['channel'] ?? 'whatsapp';
                
                if ($agentId) {
                    $agent = \App\Models\User::find($agentId);
                    if ($agent) {
                        if ($channel === 'whatsapp' && $agent->phone_number) {
                            $this->whatsapp->sendText($agent->phone_number, "[Internal Alert] " . $message);
                        } elseif ($channel === 'email' && $agent->email) {
                            \Illuminate\Support\Facades\Mail::raw($message, function($m) use ($agent) {
                                $m->to($agent->email)->subject('Automation Alert');
                            });
                        }
                    }
                }
                return 'continue';

            case 'subscribe_to_campaign':
                $campaignId = $node['data']['campaign_id'] ?? null;
                if ($campaignId) {
                    $campaign = \App\Models\Campaign::find($campaignId);
                    if ($campaign) {
                        $campaign->contacts()->syncWithoutDetaching([$run->contact_id]);
                        Log::info("AutomationRun #{$run->id}: Subscribed contact to campaign {$campaignId}");
                    }
                }
                return 'continue';

            case 'location_request':
                $text = $this->resolveVariable($run, $node['data']['text'] ?? 'Please share your location');
                $this->whatsapp->sendLocationRequest($run->contact->phone_number, $text);
                $run->update(['status' => 'waiting_input']);
                return 'wait';

            case 'contact':
                $contacts = $node['data']['contacts'] ?? [];
                if (!empty($contacts)) {
                    // Resolve variables in contact fields if necessary
                    $formattedContact = $contacts[0];
                    if (isset($formattedContact['name']['formatted_name'])) {
                        $formattedContact['name']['formatted_name'] = $this->resolveVariable($run, $formattedContact['name']['formatted_name']);
                    }
                    $this->whatsapp->sendContact($run->contact->phone_number, $formattedContact);
                }
                return 'continue';

            case 'carousel':
                $cards = $node['data']['cards'] ?? [];
                if (!empty($cards)) {
                    // Resolve variables in card text
                    $resolvedCards = array_map(function($card) use ($run) {
                        $card['body'] = $this->resolveVariable($run, $card['body'] ?? '');
                        if (isset($card['footer'])) {
                            $card['footer'] = $this->resolveVariable($run, $card['footer']);
                        }
                        return $card;
                    }, $cards);

                    $this->whatsapp->sendCarousel($run->contact->phone_number, $resolvedCards);
                    $run->update(['status' => 'waiting_input']);
                }
                return 'wait';

            case 'catalog_message':
                $catalogId   = $node['data']['catalog_id'] ?? null;
                $sendType    = $node['data']['send_type'] ?? 'multi_product';
                $productIds  = array_map('trim', explode(',', $this->resolveVariable($run, $node['data']['product_retailer_ids'] ?? '')));
                $productIds  = array_filter($productIds);
                $headerText  = $this->resolveVariable($run, $node['data']['header_text'] ?? '');
                $bodyText    = $this->resolveVariable($run, $node['data']['body_text'] ?? '');
                $footerText  = $this->resolveVariable($run, $node['data']['footer_text'] ?? '');
                $sectionTitle = $this->resolveVariable($run, $node['data']['section_title'] ?? 'Products');

                if ($catalogId && !empty($productIds)) {
                    if ($sendType === 'single_product') {
                        $this->whatsapp->sendSingleProduct(
                            $run->contact->phone_number,
                            $catalogId,
                            $productIds[0],
                            $bodyText,
                            $footerText
                        );
                    } else {
                        $sections = [[
                            'title' => $sectionTitle,
                            'product_items' => array_map(fn($id) => ['product_retailer_id' => $id], $productIds),
                        ]];
                        $this->whatsapp->sendMultiProduct(
                            $run->contact->phone_number,
                            $catalogId,
                            $headerText,
                            $bodyText,
                            $footerText,
                            $sections
                        );
                    }
                    Log::info("AutomationRun #{$run->id}: Catalog message ({$sendType}) sent with catalog {$catalogId}");
                } else {
                    Log::warning("AutomationRun #{$run->id}: catalog_message skipped — missing catalog_id or product IDs.");
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
                if (class_exists(AssignmentService::class)) {
                    (new AssignmentService)->assign($run->contact);
                }
                $run->update(['status' => 'completed']);
                return 'pause'; // Terminates naturally

            case 'loop_over_items':
                $varName = $node['data']['variable'] ?? '';
                $items = $run->state_data['variables'][$varName] ?? [];
                if (!is_array($items)) $items = [];
                
                $state = $run->state_data;
                $loopKey = "loop_idx_{$node['id']}";
                $currentIndex = $state[$loopKey] ?? 0;
                
                if ($currentIndex < count($items)) {
                    $itemVarName = $node['data']['item_variable'] ?? 'item';
                    $state['variables'][$itemVarName] = $items[$currentIndex];
                    $state[$loopKey] = $currentIndex + 1;
                    $state['loop_result'] = 'item';
                } else {
                    unset($state[$loopKey]);
                    $state['loop_result'] = 'completed';
                }
                
                $run->update(['state_data' => $state]);
                return 'continue';

            case 'sub_flow':
                $subAutomationId = $node['data']['automation_id'] ?? null;
                if ($subAutomationId) {
                    $subAutomation = Automation::find($subAutomationId);
                    if ($subAutomation) {
                        $state = $run->state_data;
                        // Push current state to a stack if we want nested returns, 
                        // for now we just jump to the sub-flow's start node
                        $startNodeId = $this->findStartNode($subAutomation->flow_data);
                        if ($startNodeId) {
                            $state['current_node_id'] = $startNodeId;
                            // We might need to handle the return path later, 
                            // but for N12 simple inline execution is fine.
                            $run->update(['state_data' => $state]);
                            return 'continue';
                        }
                    }
                }
                return 'continue';

            case 'wait_until':
                $mode = $node['data']['mode'] ?? 'business_hours';
                $resumeAt = null;

                if ($mode === 'business_hours') {
                    if (!$run->automation->team->isWithinBusinessHours()) {
                        $resumeAt = $run->automation->team->getNextOpeningTime();
                    }
                } elseif ($mode === 'specific_time') {
                    $time = $node['data']['time'] ?? '09:00';
                    $target = \Carbon\Carbon::createFromTimeString($time);
                    if ($target->isPast()) {
                        $target->addDay();
                    }
                    $resumeAt = $target->setTimezone('UTC');
                }

                if ($resumeAt) {
                    $run->update(['status' => 'paused', 'resume_at' => $resumeAt]);
                    return 'pause';
                }
                return 'continue';

            case 'set_variable':
                $vars = $run->state_data['variables'] ?? [];
                $key = $node['data']['variable'] ?? null;
                $value = $this->resolveVariable($run, $node['data']['value'] ?? '');
                $operation = $node['data']['operation'] ?? 'set';

                if ($key) {
                    switch ($operation) {
                        case 'increment':
                            $vars[$key] = ((int)($vars[$key] ?? 0)) + ((int)$value);
                            break;
                        case 'decrement':
                            $vars[$key] = ((int)($vars[$key] ?? 0)) - ((int)$value);
                            break;
                        case 'append':
                            $vars[$key] = ($vars[$key] ?? '') . $value;
                            break;
                        default:
                            $vars[$key] = $value;
                    }
                    $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
                }
                return 'continue';

            case 'rate_limit_gate':
                $max = (int)($node['data']['max_executions'] ?? 1);
                $period = $node['data']['period'] ?? '24_hours';
                $minutes = match($period) {
                    '1_hour' => 60,
                    '24_hours' => 1440,
                    '7_days' => 10080,
                    default => 1440
                };

                $ledgerCount = \App\Models\AutomationStepLedger::where('node_id', $node['id'])
                    ->where('automation_run_id', '!=', $run->id)
                    ->whereHas('run', fn($q) => $q->where('contact_id', $run->contact_id))
                    ->where('created_at', '>=', now()->subMinutes($minutes))
                    ->count();

                if ($ledgerCount >= $max) {
                    $state = $run->state_data;
                    $state['gate_result'] = 'throttled';
                    $run->update(['state_data' => $state]);
                } else {
                    $state = $run->state_data;
                    $state['gate_result'] = 'allowed';
                    $run->update(['state_data' => $state]);
                }
                return 'continue';

            case 'google_sheets':
                $spreadsheetId = $node['data']['spreadsheet_id'] ?? null;
                $values = $node['data']['values'] ?? ['{{name}}', '{{phone_number}}', '{{email}}'];
                if ($spreadsheetId) {
                    $integration = \App\Models\Integration::where('team_id', $run->automation->team_id)
                        ->where('type', 'google_drive')
                        ->where('status', 'active')
                        ->first();
                    if ($integration) {
                        $service = new \App\Services\Integrations\GoogleDriveService($integration);
                        $resolvedValues = array_map(fn($v) => $this->resolveVariable($run, (string)$v), $values);
                        try {
                            $service->appendToSheet($spreadsheetId, $resolvedValues);
                        } catch (\Exception $e) {
                            Log::error("AutomationRun #{$run->id}: Google Sheets Append Failed: " . $e->getMessage());
                            $state = $run->state_data;
                            $state['api_result'] = 'error';
                            $run->update(['state_data' => $state]);
                        }
                    }
                }
                return 'continue';

            case 'tag_contact':
            case 'remove_tag':
            case 'create_ticket':
                $this->handleUtilityNode($run, $node);
                return 'continue';

            case 'update_contact':
                $field = $node['data']['field'] ?? '';
                $value = $this->resolveVariable($run, $node['data']['value'] ?? '');
                if ($field) {
                    $contact = $run->contact;
                    if (in_array($field, ['name', 'email', 'phone_number'])) {
                        $contact->update([$field => $value]);
                    } else {
                        // Support custom fields via updateOrCreate on ContactField relationship if it exists, 
                        // or just update custom_attributes on Contact.
                        // Based on ContactService, it uses custom_attributes.
                        $attributes = $contact->custom_attributes ?? [];
                        $attributes[$field] = $value;
                        $contact->update(['custom_attributes' => $attributes]);
                    }
                }
                return 'continue';

            case 'create_deal':
                \App\Models\Deal::create([
                    'team_id' => $run->automation->team_id,
                    'contact_id' => $run->contact->id,
                    'pipeline_id' => $node['data']['pipeline_id'] ?? null,
                    'stage_id' => $node['data']['stage_id'] ?? null,
                    'title' => $this->resolveVariable($run, $node['data']['title'] ?? '{{name}} Deal'),
                    'value' => $node['data']['value'] ?? 0,
                    'status' => 'open',
                ]);
                return 'continue';

            case 'create_crm_task':
                \App\Models\CrmTask::create([
                    'team_id' => $run->automation->team_id,
                    'assigned_to_id' => $node['data']['agent_id'] ?? null,
                    'title' => $this->resolveVariable($run, $node['data']['title'] ?? 'Automated Task'),
                    'description' => $this->resolveVariable($run, $node['data']['description'] ?? ''),
                    'due_date' => now()->addDays($node['data']['due_in_days'] ?? 1),
                    'related_to_type' => \App\Models\Contact::class,
                    'related_to_id' => $run->contact->id,
                    'status' => 'pending',
                ]);
                return 'continue';

            case 'assign_agent':
                $agentId = $node['data']['agent_id'] ?? null;
                $contact = $run->contact;
                if ($agentId) {
                    $contact->update(['assigned_to' => $agentId]);
                    $conversation = app(ConversationService::class)->ensureActiveConversation($contact);
                    $conversation->update(['assigned_to' => $agentId]);
                    Log::info("AutomationRun #{$run->id}: Assigned contact {$contact->id} to agent {$agentId}");
                } else {
                    if (class_exists(AssignmentService::class)) {
                        app(AssignmentService::class)->assign($contact);
                    }
                }
                return 'continue';

            case 'stop_flow':
                $run->update(['status' => 'completed']);
                return 'pause'; // terminates naturally

            case 'trigger':
                return 'continue'; // Triggers are just entry points

            default:
                return 'continue';
        }
    }

    public function moveToNextNode(AutomationRun $run, $input = null)
    {
        if ($run->step_count >= $this->maxSteps) {
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
        $currentNode = $this->getNodeById($flowData, $currentNodeId);

        if ($currentNode && $currentNode['type'] === 'ab_split') {
            $ratio = (int) ($currentNode['data']['ratio'] ?? 50);
            $edgeArray = array_values($edges);
            if (count($edgeArray) >= 2) {
                $nextNodeId = (rand(1, 100) <= $ratio) ? $edgeArray[0]['target'] : $edgeArray[1]['target'];
                Log::info("AutomationRun #{$run->id}: A/B Split decision made. Ratio: {$ratio}%. Chosen Node: {$nextNodeId}");
            } else {
                $nextNodeId = $edgeArray[0]['target'] ?? null;
                Log::warning("AutomationRun #{$run->id}: A/B Split node has fewer than 2 edges. Defaulting to first edge.");
            }
        } elseif ($currentNode && in_array($currentNode['type'] ?? '', ['condition', 'split_by_condition'])) {
            $conditionResult = $run->state_data['condition_result'] ?? 'false';
            foreach ($edges as $edge) {
                if (($edge['condition'] ?? '') === $conditionResult) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        } elseif ($currentNode && $currentNode['type'] === 'rate_limit_gate') {
            $gateResult = $run->state_data['gate_result'] ?? 'allowed';
            foreach ($edges as $edge) {
                if (($edge['condition'] ?? '') === $gateResult) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        } elseif ($currentNode && in_array($currentNode['type'] ?? '', ['api_request', 'webhook'])) {
            $apiResult = $run->state_data['api_result'] ?? 'success';
            foreach ($edges as $edge) {
                if (($edge['condition'] ?? '') === $apiResult) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        } elseif ($currentNode && $currentNode['type'] === 'loop_over_items') {
            $loopResult = $run->state_data['loop_result'] ?? 'completed';
            foreach ($edges as $edge) {
                if (($edge['condition'] ?? '') === $loopResult) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        } elseif ($input !== null) {
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
            ExecuteAutomationNodeJob::dispatch($run->id, $nextNodeId);
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
        $recoveryMinutes = config('automation.crash_recovery_minutes', 10);
        AutomationRun::where('status', 'executing')
            ->where('last_processed_at', '<', now()->subMinutes($recoveryMinutes))
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
            try {
                $nodeId = $run->state_data['current_node_id'] ?? null;
                if (!$nodeId) {
                    Log::warning("AutomationRun #{$run->id} skipped during resume: missing current_node_id in state_data.", [
                        'state_data' => $run->state_data,
                    ]);
                    $run->update([
                        'status' => 'failed',
                        'resume_at' => null,
                    ]);
                    continue;
                }

                ExecuteAutomationNodeJob::dispatch($run->id, $nodeId);
            } catch (\Throwable $e) {
                Log::error("AutomationRun #{$run->id} failed during scheduled resume: {$e->getMessage()}", [
                    'exception' => $e,
                ]);

                $run->update([
                    'status' => 'failed',
                    'resume_at' => null,
                ]);
            }
        }
    }

    /**
     * Handles user replies to waiting sessions.
     */
    public function handleReply(Contact $contact, $messageContent, \App\Models\Message $receivedMsg = null)
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

        // 2. Identify and Process Response Data
        $input = $messageContent;
        $flowData = $run->automation->flow_data;
        $currentNodeId = $run->state_data['current_node_id'];
        $currentNode = $this->getNodeById($flowData, $currentNodeId);

        if (!$currentNode) {
            Log::warning("AutomationRun #{$run->id}: Current node {$currentNodeId} not found in handleReply.");
            return false;
        }

        $vars = $run->state_data['variables'] ?? [];

        // SPECIAL CASE: WhatsApp Flow Response (nfm_reply)
        $metadata = $receivedMsg?->metadata ?? [];
        $interactive = $metadata['interactive'] ?? [];
        if (($interactive['type'] ?? '') === 'nfm_reply') {
            $responseJson = json_decode($interactive['nfm_reply']['response_json'] ?? '{}', true);
            
            // Save full JSON if a 'variable' is specified on the node
            if (isset($currentNode['data']['variable'])) {
                $vars[$currentNode['data']['variable']] = $responseJson;
            }

            // Also flatten individual fields into variables for easier {{access}}
            foreach ($responseJson as $key => $val) {
                $vars["flow_$key"] = $val;
            }

            // Identify the completion token or input to match next nodes
            $input = $responseJson['flow_token'] ?? $messageContent;
        } 
        // SPECIAL CASE: Interactive Button or List Reply
        elseif (isset($interactive['button_reply']['id']) || isset($interactive['list_reply']['id'])) {
            $input = $interactive['button_reply']['id'] ?? $interactive['list_reply']['id'];
            if (isset($currentNode['data']['variable'])) {
                $vars[$currentNode['data']['variable']] = $input;
            }
        }
        else {
            // Standard Text Reply
            if (isset($currentNode['data']['variable'])) {
                $vars[$currentNode['data']['variable']] = $messageContent;
            }
        }

        $run->update([
            'status' => 'active',
            'state_data' => array_merge($run->state_data, ['variables' => $vars])
        ]);

        $this->moveToNextNode($run, $input);

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
        $url = $this->resolveVariable($run, $node['data']['url'] ?? '');
        $method = strtoupper($node['data']['method'] ?? 'GET');
        $headers = $node['data']['headers'] ?? [];
        $body = $this->resolveVariable($run, $node['data']['json_body'] ?? '');

        $request = \Illuminate\Support\Facades\Http::timeout(15);
        
        foreach ($headers as $key => $value) {
            $request->withHeader($key, $this->resolveVariable($run, $value));
        }

        if ($method === 'POST') {
            $response = $request->withBody($body, 'application/json')->post($url);
        } elseif ($method === 'PUT') {
            $response = $request->withBody($body, 'application/json')->put($url);
        } else {
            $response = $request->get($url);
        }

        if ($response->successful()) {
            if (isset($node['data']['save_to'])) {
                $vars = $run->state_data['variables'] ?? [];
                $vars[$node['data']['save_to']] = $response->json();
                $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
            }
            $state = $run->state_data;
            $state['api_result'] = 'success';
            $run->update(['state_data' => $state]);
        } else {
            Log::warning("AutomationRun #{$run->id}: API Request to $url failed with status {$response->status()}");
            $state = $run->state_data;
            $state['api_result'] = 'error';
            $run->update(['state_data' => $state]);
        }
    }

    protected function handleUtilityNode($run, $node)
    {
        if ($node['type'] === 'tag_contact') {
            $tagName = $node['data']['tag'] ?? 'Lead';
            $tag = \App\Models\ContactTag::firstOrCreate(['team_id' => $run->automation->team_id, 'name' => $tagName]);
            $run->contact->tags()->syncWithoutDetaching([$tag->id]);
        } elseif ($node['type'] === 'remove_tag') {
            $tagName = $node['data']['tag'] ?? '';
            if ($tagName) {
                $tag = \App\Models\ContactTag::where('team_id', $run->automation->team_id)->where('name', $tagName)->first();
                if ($tag) {
                    $run->contact->tags()->detach($tag->id);
                }
            }
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
            $trackingMessage = $this->createAutomationTrackingMessage(
                $run,
                'interactive',
                $text,
                ['node_id' => $node['id'] ?? null, 'node_type' => $node['type'], 'buttons' => $btns]
            );

            $this->whatsapp->sendInteractiveButtons($run->contact->phone_number, $text, $btns, $trackingMessage);
        } else {
            $trackingMessage = $this->createAutomationTrackingMessage(
                $run,
                'text',
                $text,
                ['node_id' => $node['id'] ?? null, 'node_type' => $node['type']]
            );

            $this->whatsapp->sendText($run->contact->phone_number, $text, $trackingMessage);
        }
    }

    protected function createAutomationTrackingMessage(AutomationRun $run, string $type, ?string $content = null, array $meta = [], array $extraFields = []): Message
    {
        $conversation = app(ConversationService::class)->ensureActiveConversation($run->contact);

        $baseMeta = [
            'is_bot' => true,
            'automation' => true,
            'automation_id' => $run->automation_id,
            'automation_run_id' => $run->id,
        ];

        return Message::create(array_merge([
            'team_id' => $run->automation->team_id,
            'contact_id' => $run->contact_id,
            'conversation_id' => $conversation->id,
            'automation_id' => $run->automation_id,
            'automation_run_id' => $run->id,
            'type' => $type,
            'direction' => 'outbound',
            'status' => 'queued',
            'content' => $content,
            'metadata' => array_merge($baseMeta, $meta),
        ], $extraFields));
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

    protected function hasErrorEdge(AutomationRun $run, $nodeId): bool
    {
        $flowData = $run->automation->flow_data;
        $edges = array_filter($flowData['edges'] ?? [], fn($e) => $e['source'] === $nodeId);
        foreach ($edges as $edge) {
            if (($edge['condition'] ?? '') === 'error') {
                return true;
            }
        }
        return false;
    }

    protected function evaluateOperator($actual, $operator, $value): bool
    {
        $strActual = strtolower((string)$actual);
        $strValue  = strtolower((string)$value);
        return match ($operator) {
            'eq'           => (string)$actual === (string)$value,
            'neq'          => (string)$actual !== (string)$value,
            'gt'           => (float)$actual > (float)$value,
            'gte'          => (float)$actual >= (float)$value,
            'lt'           => (float)$actual < (float)$value,
            'lte'          => (float)$actual <= (float)$value,
            'contains'     => str_contains($strActual, $strValue),
            'not_contains' => !str_contains($strActual, $strValue),
            'starts_with'  => str_starts_with($strActual, $strValue),
            'ends_with'    => str_ends_with($strActual, $strValue),
            'is_empty',
            'empty'        => empty($actual),
            'is_not_empty',
            'not_empty'    => !empty($actual),
            'regex'        => (bool) @preg_match('/' . $value . '/i', (string)$actual),
            default        => false,
        };
    }

    protected function resolveVariable(AutomationRun $run, string $template): string
    {
        if (!$template) return "";

        $vars = $run->state_data['variables'] ?? [];
        $contact = $run->contact;

        // System variables
        $systemVars = [
            'name' => $contact->name ?? 'there',
            'first_name' => explode(' ', $contact->name ?? '')[0],
            'phone_number' => $contact->phone_number,
            'email' => $contact->email ?? '',
            'now' => now()->format('d M Y'),
            'time' => now()->format('H:i'),
            'last_order_id' => $contact->orders()->latest()->first()?->order_id ?? '',
            'last_deal_id' => $contact->deals()->latest()->first()?->id ?? '',
        ];

        // Global variables (from Team/Settings)
        $globalVars = $run->automation->team->whatsapp_settings['global_vars'] ?? [];

        $all = array_merge($systemVars, $vars);

        // Replace {{variable_name}} or {{global.key}}
        return preg_replace_callback('/\{\{(\w+(\.\w+)?)\}\}/', function ($m) use ($all, $globalVars) {
            $key = $m[1];
            if (str_starts_with($key, 'global.')) {
                $gkey = str_replace('global.', '', $key);
                return $globalVars[$gkey] ?? $m[0];
            }
            $val = $all[$key] ?? $m[0];
            if (is_array($val) || is_object($val)) {
                return json_encode($val);
            }
            return (string)$val;
        }, $template);
    }
}
