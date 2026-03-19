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

    private static array $recursionTracker = [];

    /**
     * Trigger workflows for a given event.
     */
    public function trigger(string $triggerType, Model $subject, array $context = [])
    {
        $teamId = $subject->team_id ?? $subject->getAttribute('team_id') ?? ($subject->currentTeam?->id ?? null);

        // --- Idempotency & Concurrency Lock (UC-SAFE-05) ---
        // Prevents parallel race conditions for the exact same event on the same object.
        $subjectId = $subject->getKey();
        $lockKey = "workflow_trigger_lock:{$triggerType}:{$subjectId}";
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 5); // 5s lock is enough for most transitions

        if (!$lock->get()) {
            Log::info("WorkflowEngine: Parallel trigger detected for {$triggerType} on {$subjectId}. Skipping second execution.");
            return;
        }

        // --- Cyclic Trigger Protection ---
        $trackerKey = "{$triggerType}_{$subjectId}";
        
        if (isset(self::$recursionTracker[$trackerKey])) {
            if (self::$recursionTracker[$trackerKey] > 3) { // Max depth 3
                Log::warning("Workflow recursion limit reached for {$trackerKey}. Aborting chain.");
                $lock->release();
                return;
            }
            self::$recursionTracker[$trackerKey]++;
        } else {
            self::$recursionTracker[$trackerKey] = 1;
        }

        try {
            // Find active workflows matching the trigger
            $workflows = Workflow::where('is_active', true)
                ->where('trigger_type', $triggerType)
                ->where(function ($q) use ($teamId) {
                    if ($teamId) {
                        $q->where('team_id', $teamId); // Isolation by team
                    } else {
                        // If no team context, only allow system-wide workflows (null team_id)
                        $q->whereNull('team_id');
                    }
                })
                ->get();

            $errors = [];
            foreach ($workflows as $workflow) {
                /** @var \App\Models\Workflow $workflow */
                if ($this->shouldExecute($workflow, $subject, $context)) {
                    try {
                        $this->executeWorkflow($workflow, $subject, $context);
                    } catch (\Exception $e) {
                        Log::error("Workflow '{$workflow->name}' ({$workflow->id}) trigger failed: " . $e->getMessage());
                        $errors[] = $e;
                    }
                }
            }

            // If we had errors, re-throw the first one to signal failure to the caller (e.g. a Job)
            if (!empty($errors)) {
                throw $errors[0];
            }
        } finally {
            self::$recursionTracker[$trackerKey]--;
            if (self::$recursionTracker[$trackerKey] <= 0) {
                unset(self::$recursionTracker[$trackerKey]);
            }
            $lock->release(); // Release the concurrency lock
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
                // UNIFIED: Linear actions are now treated as a simple node list to honor delays
                $nodes = $workflow->actions->map(function($action) {
                    return [
                        'action_type' => $action->action_type,
                        'config' => $action->action_config ?? [],
                        'delay' => $action->delay_minutes ?? 0
                    ];
                })->toArray();

                $this->executeNodes($nodes, $subject, $context, $workflow, $log);
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
            
            // Re-throw to ensure Jobs can retry and callers are aware of failures (UC-ERR-01)
            throw $e;
        }
    }

    /**
     * Iterative execution of the flow definition allowing async pauses (Delays)
     */
    protected function executeNodes(array $nodes, Model $subject, array $context, ?Workflow $workflow = null, ?\App\Models\WorkflowLog $log = null)
    {
        $iterations = 0;
        $maxIterations = 1000; // Safety cap

        while (!empty($nodes)) {
            if ($iterations++ > $maxIterations) {
                Log::error("Workflow execution hit safety cap of {$maxIterations} nodes. Likely infinite loop in definition.");
                if ($log) $log->update(['status' => 'failed', 'error_message' => 'Node execution safety cap reached.']);
                return;
            }

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
                                Log::warning("Workflow Node Rate Limited (failing workflow). Key: {$key}");
                                throw new \Exception("Rate limit reached for workflow node.");
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

        // 2.1 Onboarding Status Resolution
        if (str_starts_with($field, 'onboarding.') && $subject instanceof \App\Models\User) {
            $onboardingKey = substr($field, 11);
            return $subject->onboardingStatus?->{$onboardingKey};
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
     * Map action types to handler classes.
     */
    protected function handlerMap(): array
    {
        return [
            'send_whatsapp_template' => \App\Core\Workflow\Handlers\WhatsAppActionHandler::class,
            'slack_notification' => \App\Core\Workflow\Handlers\SlackActionHandler::class,
            // Future handlers can be added here
        ];
    }

    /**
     * Execute a single action.
     */
    protected function executeAction(string $actionType, array $config, Model $subject, array $context)
    {
        // 1. Check for dedicated handler
        $map = $this->handlerMap();
        if (isset($map[$actionType])) {
            $handler = app($map[$actionType]);
            $handler->handle($config, $subject, $context);
            return;
        }

        // 2. Legacy/Simple switch for unified domain actions
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

            case 'create_task':
                $morphType = match (true) {
                    $subject instanceof Contact => Contact::class,
                    $subject instanceof Deal => Deal::class,
                    $subject instanceof \App\Models\User => \App\Models\User::class,
                    default => null
                };

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
                        'taskable_id' => $subject->getKey(),
                    ]);
                }
                break;

            case 'send_email':
                $email = $subject->email ?? ($subject instanceof Contact ? $subject->email : null);
                if ($email) {
                    Log::info("Workflow action: Sending email to " . $email);
                }
                break;

            case 'webhook':
                $url = $config['url'] ?? null;
                if (!$url) break;

                try {
                    $method = strtoupper($config['method'] ?? 'POST');
                    $headers = is_string($config['headers'] ?? '') ? json_decode($config['headers'], true) : ($config['headers'] ?? []);
                    $payload = is_string($config['payload'] ?? '') ? json_decode($config['payload'], true) : ($config['payload'] ?? []);
                    
                    $payload['_subject_id'] = $subject->id;
                    $payload['_subject_type'] = get_class($subject);
                    $payload['_context'] = $context;

                    $request = \Illuminate\Support\Facades\Http::withHeaders($headers ?: []);
                    ($config['payload_type'] ?? 'json') === 'form' ? $request->asForm() : $request->asJson();

                    $response = match ($method) {
                        'GET' => $request->get($url, $payload),
                        'PUT' => $request->put($url, $payload),
                        default => $request->post($url, $payload),
                    };

                    if ($response->failed()) {
                        throw new \Exception("HTTP Request failed with status {$response->status()}.");
                    }
                    Log::info("Workflow Webhook Sent to {$url}. Status: " . $response->status());
                } catch (\Exception $e) {
                    Log::error("Workflow Webhook Error: " . $e->getMessage());
                    throw $e;
                }
                break;

            case 'google_sheets_append':
                $spreadsheetId = $config['spreadsheet_id'] ?? null;
                if ($spreadsheetId && is_array($rowData = $config['row_data'] ?? [])) {
                    $mappedRow = array_map(function ($cell) use ($subject, $context) {
                        return (is_string($cell) && preg_match('/^\{\{(.*)\}\}$/', $cell, $matches))
                            ? $this->resolveFieldValue($subject, trim($matches[1]), $context) ?? ''
                            : $cell;
                    }, $rowData);
                    Log::info("Workflow: Appending to Google Sheet $spreadsheetId: " . json_encode($mappedRow));
                }
                break;

            case 'shopify_update_order':
                $orderId = $config['order_id'] ?? $context['order_id'] ?? null;
                if ($orderId) {
                    Log::info("Workflow: Mutating Shopify Order $orderId with tags: " . json_encode($config['tags'] ?? []));
                }
                break;
        }
    }
}
