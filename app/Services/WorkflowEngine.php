<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Team;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    /**
     * Mask sensitive keys in arrays for security & compliance.
     */
    protected function maskData(array $data): array
    {
        $sensitiveKeys = ['api_key', 'secret', 'password', 'token', 'key', 'auth', 'access_token', 'refresh_token'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskData($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }

    /**
     * Trigger workflows for a given event.
     *
     * @param string $triggerType The type of event (e.g., 'contact_created')
     * @param Model $subject The primary subject (e.g., Contact, Deal)
     * @param array $context Additional context data
     */
    public function trigger(string $triggerType, Model $subject, array $context = [])
    {
        $teamId = $subject->team_id ?? $subject->getAttribute('team_id');

        if (!$teamId) {
            Log::warning("Workflow trigger {$triggerType} ignored: No team_id found on subject.");
            return;
        }

        // Find active workflows matching the trigger
        $workflows = Workflow::where('team_id', $teamId)
            ->where('is_active', true)
            ->where('trigger_type', $triggerType)
            ->get();

        foreach ($workflows as $workflow) {
            /** @var \App\Models\Workflow $workflow */
            if ($this->shouldExecute($workflow, $subject, $context)) {
                $this->executeWorkflow($workflow, $subject, $context);
            }
        }
    }

    /**
     * Trigger workflows for a massive batch of models securely via Job Batching.
     * Perfect for thousands of contacts to execute in parallel asynchronously.
     * 
     * @param Workflow $workflow The parent workflow
     * @param \Illuminate\Database\Eloquent\Collection|array $subjects Array of models.
     */
    public function triggerBatch(Workflow $workflow, $subjects, array $context = [])
    {
        if (empty($workflow->definition)) {
            Log::warning("Batch Trigger aborted. Only JSON node definition workflows supported.");
            return null;
        }

        $jobs = [];

        foreach ($subjects as $subject) {
            $log = WorkflowLog::create([
                'workflow_id' => $workflow->id,
                'team_id' => $workflow->team_id ?? $subject->team_id ?? null,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'status' => 'waiting',
                'started_at' => now(),
                'execution_data' => $this->maskData($context),
            ]);

            $jobs[] = new \App\Jobs\ExecuteWorkflowNodesJob(
                $workflow,
                $workflow->definition,
                $subject,
                $context,
                $log
            );
        }

        if (empty($jobs))
            return null;

        Log::info("Dispatching batch of " . count($jobs) . " identical workflow executions natively to Queue.");

        return \Illuminate\Support\Facades\Bus::batch($jobs)
            ->name("Workflow Batch Trigger: " . $workflow->name)
            ->onQueue('workflows')
            ->dispatch();
    }

    /**
     * Check if workflow conditions are met.
     */
    protected function shouldExecute(Workflow $workflow, Model $subject, array $context): bool
    {
        $config = $workflow->trigger_config ?? [];

        // Example: Check if deal moved to specific stage
        if ($workflow->trigger_type === 'deal_stage_changed') {
            $targetStageId = $config['stage_id'] ?? null;
            if ($targetStageId && $subject->stage_id != $targetStageId) {
                return false;
            }
        }

        // Example: Check if tag added matches specific tag
        if ($workflow->trigger_type === 'tag_added') {
            $targetTagId = $config['tag_id'] ?? null;
            $addedTagId = $context['tag_id'] ?? null;
            if ($targetTagId && $addedTagId != $targetTagId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute a workflow.
     */
    protected function executeWorkflow(Workflow $workflow, Model $subject, array $context)
    {
        $log = WorkflowLog::create([
            'workflow_id' => $workflow->id,
            'team_id' => $workflow->team_id,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'status' => 'running',
            'started_at' => now(),
            'execution_data' => $this->maskData($context),
        ]);

        try {
            // Check for JSON Definition (Advanced Workflow)
            if (!empty($workflow->definition)) {
                // Async Execution via Work Queue
                \App\Jobs\ExecuteWorkflowNodesJob::dispatch(
                    $workflow,
                    $workflow->definition,
                    $subject,
                    $context,
                    $log
                );
            } else {
                // Legacy Logic: Linear Actions
                foreach ($workflow->actions as $action) {
                    if ($action->delay_minutes > 0) {
                        // Skip delay for MVP
                    }
                    $this->executeAction($action->action_type, $action->action_config ?? [], $subject, $context);
                }

                $log->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            $workflow->increment('execution_count');
            $workflow->update(['last_executed_at' => now()]);

        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error("Workflow {$workflow->id} failed: " . $e->getMessage());
        }
    }

    /**
     * Iterative execution of the flow definition allowing async pauses (Delays)
     */
    protected function executeNodes(array $nodes, Model $subject, array $context, ?Workflow $workflow = null, ?\App\Models\WorkflowLog $log = null)
    {
        while (!empty($nodes)) {
            $node = array_shift($nodes); // Take first node

            try {
                $delayMinutes = (int) ($node['delay'] ?? 0);
                if ($delayMinutes > 0 && $workflow) {
                    // Clear the delay so it doesn't trigger again globally when the job resumes
                    $node['delay'] = 0;
                    array_unshift($nodes, $node);

                    if ($log)
                        $log->update(['status' => 'waiting']);
                    Log::info("Workflow suspending for {$delayMinutes} minutes before executing node");

                    \App\Jobs\ExecuteWorkflowNodesJob::dispatch(
                        $workflow,
                        $nodes,
                        $subject,
                        $context,
                        $log
                    )->delay(now()->addMinutes($delayMinutes));

                    return;
                }

                if (($node['type'] ?? 'action') === 'condition') {
                    $isTrue = $this->evaluateCondition($node, $subject, $context);

                    // Prefix the resulting branch to the front of the queue to ensure sequential depth-first execution
                    if ($isTrue && !empty($node['true_nodes'])) {
                        $nodes = array_merge($node['true_nodes'], $nodes);
                    } elseif (!$isTrue && !empty($node['false_nodes'])) {
                        $nodes = array_merge($node['false_nodes'], $nodes);
                    }
                } else {
                    $actionType = $node['action_type'] ?? null;
                    $config = $node['config'] ?? [];

                    if ($actionType === 'rate_limit') {
                        if ($workflow) {
                            $key = 'workflow_limit_' . $workflow->id . '_' . $subject->id;
                            $max = (int) ($config['max_executions'] ?? 1);
                            $period = (int) ($config['period_minutes'] ?? 60);

                            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $max)) {
                                Log::info("Workflow Node Rate Limited (skipped block). Key: {$key}");
                                continue;
                            }
                            \Illuminate\Support\Facades\RateLimiter::hit($key, $period * 60);
                        }
                    } elseif ($actionType) {
                        $this->executeAction($actionType, $config, $subject, $context);

                        // Analytics: Time Saved Tracking
                        if ($workflow) {
                            $saved = (int) ($node['config']['time_saved_minutes'] ?? 5); // Default 5 mins saved per manual action replaced
                            $workflow->increment('time_saved_minutes', $saved);
                        }

                        // Analytics: Conversion Tracking
                        if ($actionType === 'mark_converted' || ($actionType === 'update_contact' && ($config['status'] ?? '') === 'converted')) {
                            if ($workflow)
                                $workflow->increment('conversions_count');
                        }
                    }
                } // close else
            } catch (\Exception $e) {
                Log::error("Workflow Node Execution Error: " . $e->getMessage());

                // --- Retry Logic ---
                $maxRetries = (int) ($node['config']['retry_count'] ?? 0);
                $currentAttempt = (int) ($node['current_attempt'] ?? 0);

                if ($maxRetries > 0 && $currentAttempt < $maxRetries) {
                    $node['current_attempt'] = $currentAttempt + 1;
                    $retryDelay = (int) ($node['config']['retry_delay_minutes'] ?? 0);

                    Log::info("Workflow Node Retrying (Attempt {$node['current_attempt']} of {$maxRetries}) in {$retryDelay}m. Error: " . $e->getMessage());

                    array_unshift($nodes, $node);

                    if ($workflow) {
                        if ($log)
                            $log->update(['status' => 'waiting']);
                        \App\Jobs\ExecuteWorkflowNodesJob::dispatch(
                            $workflow,
                            $nodes,
                            $subject,
                            $context,
                            $log
                        )->delay(now()->addMinutes($retryDelay));

                        return; // Halt execution loop while waiting
                    }
                }

                // --- Fallback (Error Path) ---
                if (!empty($node['error_nodes'])) {
                    Log::info("Workflow branching to Error/Fallback Path due to terminal node failure.");
                    $nodes = array_merge($node['error_nodes'], $nodes);
                    continue;
                }

                if ($log) {
                    $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                }

                if ($workflow) {
                    $workflow->increment('failure_count');
                }
                break; // Halt workflow execution completely
            }
        }

        // If we exhausted all nodes natively without a delay returning
        if ($log && empty($nodes)) {
            $log->update(['status' => 'completed', 'completed_at' => now()]);

            if ($workflow) {
                $workflow->increment('success_count');
            }
        }
    }

    protected function evaluateCondition(array $node, Model $subject, array $context = []): bool
    {
        $matchType = $node['config']['match_type'] ?? 'all';
        $conditions = $node['config']['conditions'] ?? [];

        // Legacy fallback
        if (empty($conditions) && isset($node['config']['field'])) {
            $conditions[] = [
                'field' => $node['config']['field'],
                'operator' => $node['config']['operator'] ?? '=',
                'value' => $node['config']['value'] ?? ''
            ];
        }

        if (empty($conditions)) {
            return false;
        }

        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? '';
            $operator = $cond['operator'] ?? '=';
            $value = $cond['value'] ?? '';

            $actualValue = $this->resolveFieldValue($subject, $field, $context);

            $isMatch = match ($operator) {
                '=' => $actualValue == $value,
                '!=' => $actualValue != $value,
                '>' => is_numeric($actualValue) && $actualValue > $value,
                '<' => is_numeric($actualValue) && $actualValue < $value,
                'contains' => is_array($actualValue)
                ? in_array($value, $actualValue)
                : str_contains((string) $actualValue, (string) $value),
                'not_contains' => is_array($actualValue)
                ? !in_array($value, $actualValue)
                : !str_contains((string) $actualValue, (string) $value),
                default => false,
            };

            if ($matchType === 'all' && !$isMatch) {
                return false; // AND logic: fails immediately if one is false
            }

            if ($matchType === 'any' && $isMatch) {
                return true; // OR logic: passes immediately if one is true
            }
        }

        return $matchType === 'all';
    }

    protected function resolveFieldValue(Model $subject, string $field, array $context = [])
    {
        // 1. Explicit Context Binding (e.g. context.message_content or context.order_amount)
        if (str_starts_with($field, 'context.')) {
            $contextKey = substr($field, 8);
            return data_get($context, $contextKey);
        }

        // 2. Implicit Context Checking (helpful for Webhook JSON fields)
        if (array_key_exists($field, $context)) {
            return $context[$field];
        }

        // 3. Subject Relationships
        if ($field === 'contact.tags') {
            return $subject->tags->pluck('name')->toArray();
        }

        // 4. Fallback to extracting attribute from Model
        $parts = explode('.', $field);
        $attribute = end($parts);

        return $subject->getAttribute($attribute);
    }

    /**
     * Execute a single action.
     */
    protected function executeAction(string $actionType, array $config, Model $subject, array $context)
    {
        switch ($actionType) {
            case 'add_tag':
                if ($subject instanceof Contact && isset($config['tag_id'])) {
                    $subject->tags()->syncWithoutDetaching([$config['tag_id']]);
                }
                break;

            case 'remove_tag':
                if ($subject instanceof Contact && isset($config['tag_id'])) {
                    $subject->tags()->detach($config['tag_id']);
                }
                break;

            case 'update_deal_stage':
                if ($subject instanceof Deal && isset($config['stage_id'])) {
                    $subject->update(['stage_id' => $config['stage_id']]);
                } elseif ($subject instanceof Contact && isset($config['stage_id'])) {
                    // Find open deal for contact or create new one?
                    // For simplicity, let's say we update the latest open deal
                    $deal = $subject->deals()->where('status', 'open')->latest()->first();
                    if ($deal) {
                        $deal->update(['stage_id' => $config['stage_id']]);
                    }
                }
                break;

            case 'assign_user':
                if (isset($config['user_id'])) {
                    if ($subject instanceof Contact) {
                        $subject->update(['assigned_to' => $config['user_id']]);
                        Log::info("Workflow assigned Contact {$subject->id} to User {$config['user_id']}");
                    } elseif ($subject instanceof Deal) {
                        $subject->update(['owner_id' => $config['user_id']]);
                        Log::info("Workflow assigned Deal {$subject->id} to User {$config['user_id']}");
                    }
                }
                break;

            case 'slack_notification':
                $webhookUrl = $config['webhook_url'] ?? get_setting('slack_webhook_url'); // Can fall back to a global setting
                $message = $config['message'] ?? 'Workflow Notification';

                if ($webhookUrl) {
                    try {
                        // Very simple var replacement
                        $message = str_replace('{subject_id}', $subject->id ?? '', $message);
                        $message = str_replace('{subject_name}', $subject->name ?? $subject->title ?? 'Record', $message);

                        \Illuminate\Support\Facades\Http::post($webhookUrl, [
                            'text' => $message,
                        ]);
                        Log::info("Workflow sent Slack notification.");
                    } catch (\Exception $e) {
                        Log::error("Workflow Slack Error: " . $e->getMessage());
                    }
                }
                break;

            case 'create_task':
                $targetSubject = $subject; // Default subject for task

                // Determine polymorphic relation
                $morphType = null;
                $morphId = null;

                if ($subject instanceof Contact) {
                    $morphType = Contact::class;
                    $morphId = $subject->getKey();
                } elseif ($subject instanceof Deal) {
                    $morphType = Deal::class;
                    $morphId = $subject->getKey();
                } elseif ($subject instanceof \App\Models\User) {
                    $morphType = \App\Models\User::class;
                    $morphId = $subject->getKey();
                }

                if ($morphType) {
                    \App\Models\CrmTask::create([
                        'team_id' => $subject instanceof \App\Models\User ? $subject->current_team_id : ($subject->team_id ?? null),
                        'title' => $config['title'] ?? 'Automated Task',
                        'description' => $config['description'] ?? 'Created by workflow',
                        'status' => 'pending',
                        'priority' => $config['priority'] ?? 'medium',
                        'due_date' => now()->addDays($config['due_days'] ?? 1),
                        'assigned_to_id' => $config['assigned_to_id'] ?? null,
                        'created_by_id' => \Illuminate\Support\Facades\Auth::id() ?? $subject->getKey(),
                        'taskable_type' => $morphType,
                        'taskable_id' => $morphId,
                    ]);
                }
                break;

            case 'send_email':
                // Placeholder for email integration
                $email = $subject->email ?? null;
                if ($subject instanceof Contact) {
                    $email = $subject->email;
                } elseif ($subject instanceof \App\Models\User) {
                    $email = $subject->email;
                }

                if ($email) {
                    Log::info("Workflow action: Sending email to " . $email);
                    // In real implementation: Mail::to($email)->send(new WorkflowEmail($config));
                }
                break;

            case 'send_whatsapp':
                $phone = $subject->phone ?? null;
                if ($subject instanceof Contact) {
                    $phone = $subject->phone_number;
                }

                if (!$phone) {
                    Log::warning("Workflow action: Cannot send WhatsApp, no phone number for subject " . $subject->getKey());
                    break;
                }

                try {
                    // 1. Construct System Team
                    $systemToken = get_setting('system_access_token');
                    $systemPhoneId = get_setting('system_phone_number_id');
                    $systemWabaId = get_setting('system_waba_id');

                    if (!$systemToken || !$systemPhoneId) {
                        Log::error("Workflow action: System WhatsApp credentials not configured.");
                        break;
                    }

                    // Use a virtual team ID (0) for system actions
                    $systemTeam = new \App\Models\Team([
                        'whatsapp_access_token' => $systemToken,
                        'whatsapp_phone_number_id' => $systemPhoneId,
                        'whatsapp_business_account_id' => $systemWabaId,
                    ]);
                    $systemTeam->id = 0; // Virtual ID
                    $systemTeam->whatsapp_setup_state = \App\Enums\IntegrationState::READY;

                    // 2. Send Template
                    $templateName = $config['template_name'] ?? 'hello_world';
                    $language = $config['language'] ?? 'en_US';

                    // Let's use a self-contained sender to avoid Trait dependency hell
                    $rawSender = new class ($systemTeam) {
                        public $token;
                        public $phoneId;
                        public $baseUrl;

                        public function __construct($team)
                        {
                            $this->token = $team->whatsapp_access_token;
                            $this->phoneId = $team->whatsapp_phone_number_id;
                            $this->baseUrl = 'https://graph.facebook.com/' . config('whatsapp.api_version', 'v21.0');
                        }

                        public function sendRawTemplate($to, $template, $lang)
                        {
                            $url = "{$this->baseUrl}/{$this->phoneId}/messages";
                            $payload = [
                            'messaging_product' => 'whatsapp',
                            'to' => $to,
                            'type' => 'template',
                            'template' => [
                                'name' => $template,
                                'language' => ['code' => $lang]
                                ]
                            ];

                            $response = \Illuminate\Support\Facades\Http::withToken($this->token)
                                ->withHeaders(['Content-Type' => 'application/json'])
                                ->post($url, $payload);

                            return $response->json();
                        }
                    };

                    $response = $rawSender->sendRawTemplate($phone, $templateName, $language);

                    if (!($response['status'] ?? false) && !isset($response['messages'])) {
                        Log::error("Workflow WhatsApp Failed: " . json_encode($response));
                    } else {
                        Log::info("Workflow WhatsApp Sent to $phone");
                    }

                } catch (\Exception $e) {
                    Log::error("Workflow WhatsApp Error: " . $e->getMessage());
                }
                break;

            case 'webhook':
                $url = $config['url'] ?? null;
                if (!$url)
                    break;

                $method = strtoupper($config['method'] ?? 'POST');
                $payloadType = $config['payload_type'] ?? 'json'; // json or form

                // Try JSON decode if headers/payload are strings, otherwise keep as array.
                $headers = is_string($config['headers'] ?? '') ? json_decode($config['headers'], true) : ($config['headers'] ?? []);
                if (!is_array($headers))
                    $headers = [];

                $payload = is_string($config['payload'] ?? '') ? json_decode($config['payload'], true) : ($config['payload'] ?? []);
                if (!is_array($payload))
                    $payload = [];

                // Append Subject context variables to payload
                $payload['_subject_id'] = $subject->id;
                $payload['_subject_type'] = get_class($subject);
                $payload['_context'] = $context;

                try {
                    $request = \Illuminate\Support\Facades\Http::withHeaders($headers);

                    if ($payloadType === 'form') {
                        $request->asForm();
                    } else {
                        $request->asJson();
                    }

                    if ($method === 'GET') {
                        $response = $request->get($url, $payload);
                    } elseif ($method === 'POST') {
                        $response = $request->post($url, $payload);
                    } elseif ($method === 'PUT') {
                        $response = $request->put($url, $payload);
                    }

                    if (isset($response) && $response->failed()) {
                        throw new \Exception("HTTP Request failed with status {$response->status()}.");
                    }

                    Log::info("Workflow Webhook Sent to {$url}. Status: " . ($response->status() ?? 'unknown'));
                } catch (\Exception $e) {
                    Log::error("Workflow Webhook Error: " . $e->getMessage());
                    throw $e; // Bubble up to trigger retry loops
                }
                break;

            case 'google_sheets_append':
                $spreadsheetId = $config['spreadsheet_id'] ?? null;
                $range = $config['range'] ?? 'Sheet1!A1';
                $rowData = is_string($config['row_data'] ?? '') ? json_decode($config['row_data'], true) : ($config['row_data'] ?? []);

                if ($spreadsheetId && is_array($rowData)) {
                    try {
                        // Mock mapping subject/context into row format for display purposes
                        // e.g. ["{{contact.name}}", "{{context.order_date}}", "New User"]
                        $mappedRow = [];
                        foreach ($rowData as $cell) {
                            if (is_string($cell) && str_starts_with($cell, '{{') && str_ends_with($cell, '}}')) {
                                $field = trim($cell, '{}');
                                $mappedRow[] = $this->resolveFieldValue($subject, $field, $context) ?? '';
                            } else {
                                $mappedRow[] = $cell;
                            }
                        }

                        Log::info("Workflow natively appending row to Google Sheet: {$spreadsheetId} | Data: " . json_encode($mappedRow));
                        // Example API:
                        // $client = new \Google\Client(); ...
                        // $service = new \Google\Service\Sheets($client);
                        // $service->spreadsheets_values->append($spreadsheetId, $range, ...);
                    } catch (\Exception $e) {
                        Log::error("Workflow Google Sheets Error: " . $e->getMessage());
                        throw clone $e;
                    }
                }
                break;

            case 'shopify_update_order':
                $orderId = $config['order_id'] ?? null;
                if (!$orderId && isset($context['order_id'])) {
                    $orderId = $context['order_id'];
                }

                $tags = $config['tags'] ?? [];
                if ($orderId) {
                    try {
                        Log::info("Workflow natively mutating Shopify Order: {$orderId} | Tags: " . json_encode($tags));
                        // Example API: 
                        // Http::withToken($shopifyToken)->put("https://{store}.myshopify.com/admin/api/2024-01/orders/{$orderId}.json", [
                        //    'order' => ['id' => $orderId, 'tags' => implode(',', $tags)]
                        // ]);
                    } catch (\Exception $e) {
                        Log::error("Workflow Shopify Error: " . $e->getMessage());
                        throw clone $e;
                    }
                }
                break;
        }
    }
}
