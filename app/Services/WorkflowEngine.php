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
            if ($this->shouldExecute($workflow, $subject, $context)) {
                $this->executeWorkflow($workflow, $subject, $context);
            }
        }
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
            'execution_data' => $context,
        ]);

        try {
            foreach ($workflow->actions as $action) {
                $this->executeAction($action, $subject, $context);
            }

            $log->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

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
     * Execute a single action.
     */
    protected function executeAction(WorkflowAction $action, Model $subject, array $context)
    {
        // Handle delays (TODO: Implement job dispatch for delayed actions)
        if ($action->delay_minutes > 0) {
            // For now, we skip delay implementation and execute immediately
            // In production, this would dispatch a delayed job
        }

        $config = $action->action_config ?? [];

        switch ($action->action_type) {
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
                    $rawSender = new class($systemTeam) { 
                        public $token;
                        public $phoneId;
                        public $baseUrl;

                        public function __construct($team) { 
                            $this->token = $team->whatsapp_access_token;
                            $this->phoneId = $team->whatsapp_phone_number_id;
                            $this->baseUrl = 'https://graph.facebook.com/' . config('whatsapp.api_version', 'v21.0');
                        }

                        public function sendRawTemplate($to, $template, $lang) {
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
        }
    }
}
