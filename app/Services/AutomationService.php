<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Check if an automation should be triggered.
     * Supports Partial Matching now.
     */
    public function checkTriggers(Contact $contact, $messageContent)
    {
        $messageContent = strtolower(trim($messageContent));

        // 1. Keyword Triggers
        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'keyword')
            ->get();

        foreach ($automations as $automation) {
            $keywords = $automation->trigger_config['keywords'] ?? [];
            foreach ($keywords as $keyword) {
                if (str_contains($messageContent, strtolower($keyword))) {
                    $this->start($automation, $contact);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for special event-based triggers.
     */
    public function checkSpecialTriggers(Contact $contact, $type)
    {
        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', $type)
            ->get();

        foreach ($automations as $automation) {
            $this->start($automation, $contact);
            return true; // Trigger first matching automation
        }

        return false;
    }

    /**
     * Check for triggers based on template button responses.
     */
    public function checkTemplateTriggers(Contact $contact, $buttonText)
    {
        $buttonText = strtolower(trim($buttonText));

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'template_response')
            ->get();

        foreach ($automations as $automation) {
            $config = $automation->trigger_config ?? [];
            $matchText = strtolower(trim($config['button_text'] ?? ''));

            if ($buttonText === $matchText && !empty($matchText)) {
                $this->start($automation, $contact);
                return true;
            }
        }

        return false;
    }

    /**
     * Check for triggers based on message status updates (e.g. Delivered).
     */
    public function checkStatusTriggers(Contact $contact, $templateName, $status)
    {
        if ($status !== 'delivered') {
            return false;
        }

        $automations = Automation::where('team_id', $contact->team_id)
            ->where('is_active', true)
            ->where('trigger_type', 'template_delivered')
            ->get();

        foreach ($automations as $automation) {
            $config = $automation->trigger_config ?? [];
            $matchTemplate = $config['template_name'] ?? null;

            if ($templateName === $matchTemplate && !empty($matchTemplate)) {
                $this->start($automation, $contact);
                return true;
            }
        }

        return false;
    }

    /**
     * Start a new automation flow.
     */
    public function start(Automation $automation, Contact $contact)
    {
        // Stop any existing runs
        AutomationRun::where('contact_id', $contact->id)->where('status', 'active')->update(['status' => 'interrupted']);

        $flowData = $automation->flow_data;
        $startNodeId = $this->findStartNode($flowData);

        if (!$startNodeId) {
            Log::error("Automation {$automation->id} has no start node.");
            return;
        }

        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'contact_id' => $contact->id,
            'status' => 'active',
            'state_data' => ['current_node_id' => $startNodeId, 'variables' => []],
        ]);

        $this->executeNode($run);
    }

    /**
     * Continue the automation (e.g. after user reply).
     */
    public function handleReply(Contact $contact, $messageContent)
    {
        $run = AutomationRun::where('contact_id', $contact->id)
            ->where('status', 'waiting_input')
            ->first();

        if (!$run) {
            // Check if there is a PAUSED run (Delayed). If user replies, we stop the abandonment flow.
            // "Stop if replied"
            $pausedRun = AutomationRun::where('contact_id', $contact->id)
                ->where('status', 'paused')
                ->first();

            if ($pausedRun) {
                // User replied! Cancel the follow-up.
                $pausedRun->update(['status' => 'completed', 'resume_at' => null]);
                return false; // Let it fallback to inbox/agent or other triggers
            }

            return false;
        }

        // Save input if needed
        $currentNode = $this->getNode($run, $run->state_data['current_node_id']);
        if ($currentNode && isset($currentNode['data']['variable'])) {
            $vars = $run->state_data['variables'] ?? [];
            $vars[$currentNode['data']['variable']] = $messageContent;
            $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
        }

        // Logic to clear waiting state and move next
        $run->status = 'active';
        $run->save();

        $this->moveToNextNode($run, $messageContent); // Move based on input logic or simple next

        return true;
    }

    protected function executeNode(AutomationRun $run)
    {
        $nodeId = $run->state_data['current_node_id'];
        $flowData = $run->automation->flow_data;
        $node = $this->getNodeById($flowData, $nodeId);

        if (!$node) {
            $run->update(['status' => 'completed']);
            return;
        }

        // Process Node Type
        switch ($node['type']) {
            case 'message':
            case 'text':
                $this->whatsapp->setTeam($run->automation->team);
                $this->whatsapp->sendText($run->contact->phone_number, $node['data']['text'] ?? '');
                $this->moveToNextNode($run);
                break;

            case 'image':
            case 'video':
            case 'audio':
            case 'file':
                $this->whatsapp->setTeam($run->automation->team);
                $url = $node['data']['url'] ?? null;
                $caption = $node['data']['caption'] ?? '';
                if ($url) {
                    $this->whatsapp->sendMedia($run->contact->phone_number, $node['type'], $url, $caption);
                }
                $this->moveToNextNode($run);
                break;

            case 'interactive_button':
                $this->whatsapp->setTeam($run->automation->team);
                $text = $node['data']['text'] ?? '';
                $buttons = [];
                foreach ($node['data']['buttons'] ?? [] as $btn) {
                    $buttons[$btn['id']] = $btn['title'];
                }
                if (!empty($buttons)) {
                    $this->whatsapp->sendInteractiveButtons($run->contact->phone_number, $text, $buttons);
                } else {
                    $this->whatsapp->sendText($run->contact->phone_number, $text);
                }
                $run->update(['status' => 'waiting_input']);
                break;

            case 'interactive_list':
                $this->whatsapp->setTeam($run->automation->team);
                $text = $node['data']['text'] ?? '';
                $buttonText = $node['data']['button_text'] ?? 'Options';
                $sections = $node['data']['sections'] ?? [];

                // WhatsAppService might need a specific format for lists, but for now we follow button logic if sendInteractiveList exists
                if (method_exists($this->whatsapp, 'sendInteractiveList')) {
                    $this->whatsapp->sendInteractiveList($run->contact->phone_number, $text, $buttonText, $sections);
                } else {
                    // Fallback to buttons or text if method doesn't exist
                    $this->whatsapp->sendText($run->contact->phone_number, $text);
                }
                $run->update(['status' => 'waiting_input']);
                break;

            case 'template':
                $this->whatsapp->setTeam($run->automation->team);
                $templateName = $node['data']['template_name'] ?? null;
                $language = $node['data']['language'] ?? 'en_US';

                if ($templateName) {
                    $response = $this->whatsapp->sendTemplate(
                        $run->contact->phone_number,
                        $templateName,
                        $language
                    );

                    if ($response['success'] ?? false) {
                        $wamid = $response['data']['messages'][0]['id'] ?? null;
                        if ($wamid) {
                            \App\Models\Message::create([
                                'team_id' => $run->automation->team_id,
                                'contact_id' => $run->contact_id,
                                'conversation_id' => $run->contact->activeConversation->id ?? (new \App\Services\ConversationService())->ensureActiveConversation($run->contact)->id,
                                'whatsapp_message_id' => $wamid,
                                'direction' => 'outbound',
                                'type' => 'template',
                                'status' => 'sent',
                                'content' => "Template: {$templateName}",
                                'sent_at' => now(),
                                'metadata' => [
                                    'template_name' => $templateName,
                                    'automation_id' => $run->automation_id,
                                    'run_id' => $run->id
                                ]
                            ]);
                        }
                    }
                }
                $this->moveToNextNode($run);
                break;

            case 'question':
            case 'user_input':
                $this->whatsapp->setTeam($run->automation->team);
                $nodeContent = $node['data']['text'] ?? $node['data']['question'] ?? '';
                $options = $node['data']['options'] ?? [];
                if (!empty($options)) {
                    $buttons = [];
                    foreach ($options as $opt) {
                        $buttons[$opt] = $opt;
                    }
                    $this->whatsapp->sendInteractiveButtons($run->contact->phone_number, $nodeContent, $buttons);
                } else {
                    $this->whatsapp->sendText($run->contact->phone_number, $nodeContent);
                }
                // Wait for input
                $run->update(['status' => 'waiting_input']);
                break;

            case 'trigger': // START node
                $this->moveToNextNode($run);
                break;

            case 'webhook':
            case 'api_request':
                // External API Connector Module
                $url = $node['data']['url'] ?? '';
                $method = $node['data']['method'] ?? 'GET';
                $saveTo = $node['data']['save_to'] ?? null;

                // Variable Substitution
                $vars = $run->state_data['variables'] ?? [];

                // Add System Variables
                $systemVars = [
                    'contact.phone' => $run->contact->phone_number,
                    'contact.name' => $run->contact->name,
                    'contact.id' => $run->contact->id,
                ];

                $allVars = array_merge($vars, $systemVars);

                foreach ($allVars as $key => $val) {
                    // Support simple {{variable}} syntax
                    $url = str_replace("{{" . $key . "}}", $val, $url);
                }

                try {
                    $response = [];
                    if (strtoupper($method) === 'GET') {
                        $response = \Illuminate\Support\Facades\Http::get($url)->json();
                    } elseif (strtoupper($method) === 'POST') {
                        // Example: Send all variables as body
                        $response = \Illuminate\Support\Facades\Http::post($url, $vars)->json();
                    }

                    if ($saveTo && $response) {
                        // Store full JSON or specific key? For MVP store full encoded or specific field if implemented
                        $vars[$saveTo] = is_array($response) ? json_encode($response) : $response;
                        $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
                    }
                } catch (\Exception $e) {
                    Log::error("Automation API Error: " . $e->getMessage());
                }

                $this->moveToNextNode($run);
                break;

            case 'tag_contact':
                // Lead Qualification: Tag the user
                $tagName = $node['data']['tag'] ?? 'Lead';

                // Find or Create Tag
                $tag = \App\Models\ContactTag::firstOrCreate(
                    ['team_id' => $run->automation->team_id, 'name' => $tagName]
                );

                // Attach to Contact via Pivot (Manual SQL for pivot or relationship)
                // Assuming Contact model doesn't have BelongsToMany yet, using DB for speed or Model if exists
                // Let's check Contact model. If not setup, DB insert is safe.
                \Illuminate\Support\Facades\DB::table('contact_tag_pivot')->updateOrInsert(
                    ['contact_id' => $run->contact_id, 'tag_id' => $tag->id]
                );

                $this->moveToNextNode($run);
                break;

            case 'create_ticket':
                // Support Automation: Create Ticket
                \App\Models\Ticket::create([
                    'team_id' => $run->automation->team_id,
                    'contact_id' => $run->contact_id,
                    'subject' => $node['data']['subject'] ?? 'New Support Ticket',
                    'priority' => $node['data']['priority'] ?? 'medium',
                    'description' => $node['data']['description'] ?? 'Created via Bot',
                    'status' => 'open'
                ]);

                // Notify? (Optional)
                $this->moveToNextNode($run);
                break;

            case 'handover':
                // Assign to agent logic
                // Notify Team Members?
                (new \App\Services\AssignmentService)->assignToBestAgent($run->automation->team, $run->contact);

                // Also Send a System Note
                (new \App\Services\AssignmentService)->createSystemNote(
                    $run->automation->team,
                    $run->contact,
                    "Bot Handover: " . ($node['data']['note'] ?? 'Lead Qualified')
                );

                $run->update(['status' => 'completed']);
                break;

            case 'delay':
                // UC-17: Abandoned Lead Follow-up
                // Pause execution for X minutes/hours
                $minutes = (int) ($node['data']['minutes'] ?? 0);
                $hours = (int) ($node['data']['hours'] ?? 0);
                $totalMinutes = $minutes + ($hours * 60);

                if ($totalMinutes > 0) {
                    $run->update([
                        'status' => 'paused',
                        'resume_at' => now()->addMinutes($totalMinutes)
                    ]);
                    // Do NOT call moveToNextNode immediately
                } else {
                    $this->moveToNextNode($run);
                }
                break;

            case 'openai':
                $teamId = $run->automation->team_id;
                $service = new KnowledgeBaseService();

                // 1. Get Settings
                $apiKey = \App\Models\Setting::where('key', "ai_openai_api_key_$teamId")->value('value');
                $model = \App\Models\Setting::where('key', "ai_openai_model_$teamId")->value('value') ?: 'gpt-4o';
                $persona = \App\Models\Setting::where('key', "ai_persona_$teamId")->value('value') ?: "You are a helpful business assistant.";

                $temperature = (float) \App\Models\Setting::where('key', "ai_temperature_$teamId")->value('value') ?: 0.7;
                $showHeader = (bool) \App\Models\Setting::where('key', "ai_show_header_$teamId")->value('value');
                $showFooter = (bool) \App\Models\Setting::where('key', "ai_show_footer_$teamId")->value('value');
                $header = \App\Models\Setting::where('key', "ai_header_$teamId")->value('value');
                $footer = \App\Models\Setting::where('key', "ai_footer_$teamId")->value('value');

                $showStop = (bool) \App\Models\Setting::where('key', "ai_show_stop_$teamId")->value('value');
                $stopWords = \App\Models\Setting::where('key', "ai_stop_keywords_$teamId")->value('value');

                $showRetry = (bool) \App\Models\Setting::where('key', "ai_show_retry_$teamId")->value('value');
                $retries = (int) \App\Models\Setting::where('key', "ai_retry_$teamId")->value('value') ?: 1;

                $showFallback = (bool) \App\Models\Setting::where('key', "ai_show_fallback_$teamId")->value('value');
                $fallback = \App\Models\Setting::where('key', "ai_fallback_$teamId")->value('value') ?: "I'm sorry, I'm having trouble processing that right now.";

                if (!$apiKey) {
                    Log::error("OpenAI API Key missing for team $teamId");
                    $this->moveToNextNode($run);
                    break;
                }

                // 2. Prepare Context
                $userPrompt = $node['data']['prompt'] ?? '';

                // Variable substitution in prompt
                $vars = $run->state_data['variables'] ?? [];
                foreach ($vars as $k => $v) {
                    $userPrompt = str_replace('{{' . $k . '}}', (string) $v, $userPrompt);
                }

                $context = $service->searchContext($teamId, $userPrompt);

                $finalSystemPrompt = $persona . "\n\nBusiness Context:\n" . $context;

                // 3. Call OpenAI with Retries
                $aiResult = null;
                $success = false;
                $attempt = 0;
                $maxAttempts = $showRetry ? ($retries + 1) : 1;

                while ($attempt < $maxAttempts && !$success) {
                    $attempt++;
                    try {
                        $payload = [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'system', 'content' => $finalSystemPrompt],
                                ['role' => 'user', 'content' => $userPrompt],
                            ],
                            'temperature' => $temperature,
                        ];

                        if ($showStop && !empty($stopWords)) {
                            // Split by comma and trim
                            $stopArray = array_map('trim', explode(',', $stopWords));
                            $payload['stop'] = array_slice($stopArray, 0, 4); // OpenAI limit is 4
                        }

                        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                            ->timeout(30)
                            ->post('https://api.openai.com/v1/chat/completions', $payload);

                        if ($response->successful()) {
                            $aiResult = $response->json('choices.0.message.content');

                            // Apply Header/Footer
                            if ($showHeader && !empty($header)) {
                                $aiResult = $header . "\n" . $aiResult;
                            }
                            if ($showFooter && !empty($footer)) {
                                $aiResult = $aiResult . "\n" . $footer;
                            }

                            $success = true;
                        } else {
                            Log::error("OpenAI API Error (Attempt $attempt): " . $response->body());
                            if ($attempt >= $maxAttempts && $showFallback) {
                                $aiResult = $fallback;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Automation OpenAI Error (Attempt $attempt): " . $e->getMessage());
                        if ($attempt >= $maxAttempts && $showFallback) {
                            $aiResult = $fallback;
                        }
                    }
                }

                if ($aiResult !== null) {
                    $saveTo = $node['data']['save_to'] ?? 'ai_response';
                    $vars = $run->state_data['variables'] ?? [];
                    $vars[$saveTo] = $aiResult;
                    $run->update(['state_data' => array_merge($run->state_data, ['variables' => $vars])]);
                }

                $this->moveToNextNode($run);
                break;
        }
    }

    /**
     * Resume delayed automations.
     * Called by Schedule (Cron).
     */
    public function resumeScheduledRuns()
    {
        $dueRuns = AutomationRun::where('status', 'paused')
            ->whereNotNull('resume_at')
            ->where('resume_at', '<=', now())
            ->get();

        foreach ($dueRuns as $run) {
            $run->update([
                'status' => 'active',
                'resume_at' => null
            ]);

            // Move to next step (Trigger Reminder)
            $this->moveToNextNode($run);
        }
    }

    protected function moveToNextNode(AutomationRun $run, $input = null)
    {
        $flowData = $run->automation->flow_data;
        $currentNodeId = $run->state_data['current_node_id'];

        // Find edges from current node
        $potentialEdges = [];
        foreach ($flowData['edges'] as $edge) {
            if ($edge['source'] === $currentNodeId) {
                $potentialEdges[] = $edge;
            }
        }

        if (empty($potentialEdges)) {
            $run->update(['status' => 'completed']);
            return;
        }

        $nextNodeId = null;

        // 1. Try to find a matching condition
        if ($input !== null) {
            foreach ($potentialEdges as $edge) {
                if (isset($edge['condition']) && strtolower(trim($input)) === strtolower(trim($edge['condition']))) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        }

        // 2. If no condition matched, take the first edge without a condition (default)
        if (!$nextNodeId) {
            foreach ($potentialEdges as $edge) {
                if (!isset($edge['condition']) || empty($edge['condition'])) {
                    $nextNodeId = $edge['target'];
                    break;
                }
            }
        }

        // 3. Fallback to first edge if nothing found
        if (!$nextNodeId && !empty($potentialEdges)) {
            $nextNodeId = $potentialEdges[0]['target'];
        }

        if ($nextNodeId) {
            $state = $run->state_data;
            $state['current_node_id'] = $nextNodeId;
            $run->update(['state_data' => $state]);

            $this->executeNode($run);
        } else {
            $run->update(['status' => 'completed']);
        }
    }

    protected function getNodeById($flowData, $id)
    {
        foreach ($flowData['nodes'] as $node) {
            if ($node['id'] === $id)
                return $node;
        }
        return null;
    }

    protected function findStartNode($flowData)
    {
        foreach ($flowData['nodes'] as $node) {
            if ($node['type'] === 'trigger')
                return $node['id'];
        }
        return null;
    }

    protected function getNode($run, $nodeId)
    {
        return $this->getNodeById($run->automation->flow_data, $nodeId);
    }
}
