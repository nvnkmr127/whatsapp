<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;
    protected $token;
    protected $phoneId;
    protected $team;
    public $isBot = false;

    public function __construct(?Team $team = null)
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
        if ($team) {
            $this->setTeam($team);
        }
    }

    /**
     * Set the context for a specific team.
     */
    public function setTeam(Team $team)
    {
        $this->team = $team; // Store reference
        $this->token = (string) $team->whatsapp_access_token;
        $this->phoneId = $team->whatsapp_phone_number_id;

        if (!$this->token || !$this->phoneId) {
            // Log warning instead of throwing if we're just initializing
            Log::warning("WhatsApp credentials not configured for team: {$team->name}");
        }

        return $this;
    }

    /**
     * Format Key-Value variables into WhatsApp API Component structure.
     * Assumes Body parameters are sequential {{1}}, {{2}}...
     */
    protected function formatTemplateVariables(array $variables)
    {
        if (empty($variables)) {
            return [];
        }

        $parameters = [];

        // If associative array (key=>val), we just take values as sequence
        // If indexed array, we use as is.
        foreach ($variables as $value) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string) $value,
            ];
        }

        return [
            [
                'type' => 'body',
                'parameters' => $parameters,
            ]
        ];
    }

    /**
     * Get or create contact with race condition protection.
     * 
     * @param string $phone Phone number in any format
     * @return \App\Models\Contact
     */
    protected function getOrCreateContact(string $phone)
    {
        $phone = \App\Helpers\PhoneNumberHelper::normalize($phone);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($phone) {
            $contact = \App\Models\Contact::lockForUpdate()
                ->where('team_id', $this->team->id)
                ->where('phone_number', $phone)
                ->first();

            if (!$contact) {
                $contact = \App\Models\Contact::create([
                    'team_id' => $this->team->id,
                    'phone_number' => $phone,
                ]);
            }

            return $contact;
        });
    }

    /**
     * Send a plain text message.
     */
    public function sendText($to, $message, $existingMessage = null)
    {
        // Find contact to check policy
        $contact = $this->getOrCreateContact($to);

        // Enforce 24h Policy for Free Text
        $policy = app(PolicyService::class);
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked free message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send free text message. 24-hour window is closed or User opted out. (Policy UC-03). Please use a Template.");
        }

        // Rule 1: Messaging Lock & Plan Limits
        $this->verifyReadyToSend();
        if (!$this->team->canAccess('send_message')) {
            throw new \Exception("Monthly message limit reached or subscription inactive. (Policy UC-20).");
        }

        // 1. Resolve Conversation
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);

        // 2. Pre-Persist or Use Existing
        $msg = $existingMessage;
        if (!$msg) {
            $msg = \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'type' => 'text',
                'direction' => 'outbound',
                'status' => 'queued',
                'content' => $message,
                'metadata' => ['is_bot' => $this->isBot],
            ]);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        try {
            $response = $this->sendRequest('messages', $payload);

            if ($response['success'] ?? false) {
                $wamId = $response['data']['messages'][0]['id'] ?? null;
                $conversationService->handleOutboundMessage($conversation, $this->isBot);

                $msg->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $wamId,
                    'sent_at' => now(),
                ]);
            } else {
                $msg->update([
                    'status' => 'failed',
                    'error_message' => json_encode($response['error'] ?? 'Unknown Error'),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            $msg->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send a media message (Image, Video, Audio, Document).
     */
    public function sendMedia($to, $type, $link, $caption = null, $existingMessage = null)
    {
        // Find contact to check policy
        $contact = $this->getOrCreateContact($to);

        // Enforce 24h Policy for Free Messages (Media is a free message)
        $policy = app(PolicyService::class);
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked media message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send media. 24-hour window is closed or User opted out. Please use a Template.");
        }

        // Rule 1: Messaging Lock & Plan Limits
        $this->verifyReadyToSend();
        if (!$this->team->canAccess('send_message')) {
            throw new \Exception("Monthly message limit reached or subscription inactive.");
        }

        // 1. Resolve Conversation
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);

        // 2. Pre-Persist or Use Existing
        $msg = $existingMessage;
        if (!$msg) {
            $msg = \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'type' => $type,
                'direction' => 'outbound',
                'status' => 'queued',
                'media_type' => $type,
                'media_url' => $link,
                'caption' => $caption,
                'metadata' => ['is_bot' => $this->isBot],
            ]);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $type,
            $type => [
                'link' => $link,
                'caption' => $caption
            ]
        ];

        try {
            $response = $this->sendRequest('messages', $payload);

            if ($response['success'] ?? false) {
                $wamId = $response['data']['messages'][0]['id'] ?? null;
                $conversationService->handleOutboundMessage($conversation, $this->isBot);

                $msg->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $wamId,
                    'sent_at' => now(),
                ]);
            } else {
                $msg->update([
                    'status' => 'failed',
                    'error_message' => json_encode($response['error'] ?? 'Unknown Error'),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            $msg->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send interactive buttons.
     */
    public function sendInteractiveButtons($to, $text, array $buttons, $existingMessage = null)
    {
        // Find contact to check policy
        $contact = $this->getOrCreateContact($to);

        // Enforce 24h Policy for Free Messages (Interactive is a free message)
        $policy = app(PolicyService::class);
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked interactive message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send interactive buttons. 24-hour window is closed or User opted out. Please use a Template.");
        }

        // Rule 1: Messaging Lock & Plan Limits
        $this->verifyReadyToSend();
        if (!$this->team->canAccess('send_message')) {
            throw new \Exception("Monthly message limit reached or subscription inactive.");
        }

        // 1. Resolve Conversation
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);

        // 2. Pre-Persist or Use Existing
        $msg = $existingMessage;
        if (!$msg) {
            $msg = \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'type' => 'interactive',
                'direction' => 'outbound',
                'status' => 'queued',
                'content' => $text,
                'metadata' => ['buttons' => $buttons, 'is_bot' => $this->isBot],
            ]);
        }

        $buttonObjects = [];
        foreach ($buttons as $id => $title) {
            $buttonObjects[] = [
                'type' => 'reply',
                'reply' => ['id' => (string) $id, 'title' => substr($title, 0, 20)] // Max 20 chars for title
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $text],
                'action' => ['buttons' => $buttonObjects]
            ]
        ];

        try {
            $response = $this->sendRequest('messages', $payload);

            if ($response['success'] ?? false) {
                $wamId = $response['data']['messages'][0]['id'] ?? null;
                $conversationService->handleOutboundMessage($conversation, $this->isBot);

                $msg->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $wamId,
                    'sent_at' => now(),
                ]);
            } else {
                $msg->update([
                    'status' => 'failed',
                    'error_message' => json_encode($response['error'] ?? 'Unknown Error'),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            $msg->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send a Flow via Interactive Message.
     */
    public function sendFlow($to, $flowId, $headline, $body, $cta, $mode = 'draft', $initialScreen = null, $data = [], $existingMessage = null)
    {
        // Find contact to check policy
        $contact = $this->getOrCreateContact($to);

        // Enforce 24h Policy for Free Messages
        $policy = app(PolicyService::class);
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked flow message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send flow. 24-hour window is closed or User opted out. Please use a Template.");
        }

        // ENTRY POINT ENFORCEMENT
        // Resolve Flow Model
        $flow = \App\Models\WhatsAppFlow::where('team_id', $this->team->id)
            ->where(function ($q) use ($flowId) {
                $q->where('flow_id', $flowId)->orWhere('id', $flowId);
            })->first();

        if ($flow) {
            $epValidator = new \App\Validators\FlowEntryPointValidator();
            // Flows sent via API (interactive message) fall under 'interactive' entry point
            $epResult = $epValidator->validate($flow, 'interactive');
            if (!$epResult->isValid()) {
                throw new \Exception("Flow Entry Point Blocked: " . $epResult->getBlockingReason());
            }
            // Use resolved flow_id
            $flowId = $flow->flow_id;
        } else {
            throw new \Exception("Flow ID {$flowId} not found in system. Cannot verify entry point configuration.");
        }

        // Rule 1: Messaging Lock & Plan Limits
        $this->verifyReadyToSend();
        if (!$this->team->canAccess('send_message')) {
            throw new \Exception("Monthly message limit reached or subscription inactive.");
        }

        // 1. Resolve Conversation
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);

        // 2. Pre-Persist or Use Existing
        $msg = $existingMessage;
        if (!$msg) {
            $msg = \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'type' => 'interactive',
                'direction' => 'outbound',
                'status' => 'queued',
                'content' => $headline,
                'metadata' => [
                    'flow_id' => $flowId,
                    'mode' => $mode,
                    'is_bot' => $this->isBot
                ],
            ]);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'flow',
                'header' => ['type' => 'text', 'text' => $headline],
                'body' => ['text' => $body],
                'footer' => ['text' => ''],
                'action' => [
                    'name' => 'flow',
                    'parameters' => [
                        'flow_message_version' => '3',
                        'flow_token' => json_encode(['id' => $flowId, 'v' => $flow->latest_version_number ?? 0]), // Track version
                        'flow_id' => (string) $flowId,
                        'flow_cta' => $cta,
                        'flow_action' => 'navigate',
                        'flow_action_payload' => [
                            'screen' => $initialScreen ?? ($flow->design_data['screens'][0]['id'] ?? 'screen_welcome'),
                            'data' => (object) $data
                        ]
                    ]
                ]
            ]
        ];

        // Dev Mode/Production Mode Toggle
        if ($mode === 'draft') {
            $payload['interactive']['action']['parameters']['mode'] = 'draft';
        } else {
            $payload['interactive']['action']['parameters']['mode'] = 'published';
        }

        try {
            $response = $this->sendRequest('messages', $payload);

            if ($response['success'] ?? false) {
                $wamId = $response['data']['messages'][0]['id'] ?? null;
                $conversationService->handleOutboundMessage($conversation, $this->isBot);

                $msg->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $wamId,
                    'sent_at' => now(),
                ]);
            } else {
                $msg->update([
                    'status' => 'failed',
                    'error_message' => json_encode($response['error'] ?? 'Unknown Error'),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            $msg->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    public function sendTemplate($to, $templateName, $language = 'en_US', $bodyParams = [], $headerParams = [], $footerParams = [], $campaignId = null, $existingMessage = null)
    {
        // Fetch Template first to understand structure
        $tpl = \App\Models\WhatsappTemplate::where('team_id', $this->team->id)
            ->where('name', $templateName)
            ->where('language', $language)
            ->first();

        // Language Fallback
        if (!$tpl) {
            $fallback = \App\Models\WhatsappTemplate::where('team_id', $this->team->id)
                ->where('name', $templateName)
                ->where('language', 'en_US')
                ->first();
            if ($fallback) {
                $tpl = $fallback;
                $language = 'en_US';
            }
        }

        if (!$tpl) {
            Log::error("Template not found for team {$this->team->id}", ['name' => $templateName, 'lang' => $language]);
            return ['success' => false, 'error' => 'Template not found in local database. Please sync templates.'];
        }

        // Readiness Pre-flight check (Rules UC-04, UC-05)
        $validator = new \App\Validators\TemplateValidator();
        $validator->validate($tpl, [
            'header_media_url' => $headerParams[0] ?? null
        ]);

        if ($tpl->readiness_score < 70) {
            Log::warning("Blocked sendTemplate due to low readiness: {$tpl->name}", ['score' => $tpl->readiness_score]);
            $reasons = collect($tpl->validation_results)->pluck('description')->implode(', ');
            return [
                'success' => false,
                'error' => "Template is not ready (Score: {$tpl->readiness_score}). Reasons: {$reasons}."
            ];
        }

        // Rule 1: Messaging Lock
        $this->verifyReadyToSend();

        $components = [];

        // Handle Header
        if (!empty($headerParams)) {
            $headerComponent = collect($tpl->components ?? [])->firstWhere('type', 'HEADER');
            $headerType = 'text';
            if ($headerComponent && in_array($headerComponent['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $headerType = strtolower($headerComponent['format']);
            }

            $hParams = [];
            foreach ($headerParams as $hp) {
                if ($headerType === 'text') {
                    $hParams[] = ['type' => 'text', 'text' => $hp];
                } else {
                    $hParams[] = ['type' => $headerType, $headerType => ['link' => $hp]];
                }
            }

            if (!empty($hParams)) {
                $components[] = [
                    'type' => 'header',
                    'parameters' => $hParams
                ];
            }
        }

        // Add Body Component
        if (!empty($bodyParams)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $this->formatTemplateVariables($bodyParams)[0]['parameters']
            ];
        }

        // Add Footer Component (if any)
        if (!empty($footerParams)) {
            $components[] = [
                'type' => 'footer',
                'parameters' => $this->formatTemplateVariables($footerParams)[0]['parameters']
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components
            ]
        ];

        // 3. Resolve Contact ID
        $contact = $this->getOrCreateContact($to);

        // --- POLICY GUARDRAILS (UC-07) ---
        $policy = app(PolicyService::class);
        $category = strtoupper($tpl->category ?? 'MARKETING');

        if ($category === 'MARKETING' && !$contact->hasValidConsent()) {
            return [
                'success' => false,
                'error' => "CAT_MARKETING_NO_OPT_IN: Marketing message blocked."
            ];
        }

        if ($category === 'UTILITY' && $contact->opt_in_status === 'opted_out') {
            return [
                'success' => false,
                'error' => "CAT_UTILITY_BLOCKED: Transactional message blocked."
            ];
        }

        if (!$policy->canSendTemplate($contact, strtolower($category))) {
            return ['success' => false, 'error' => 'Blocked by General Messaging Policy.'];
        }

        // 5. Check Wallet & Plan Limits
        if (!$this->team->canAccess('send_message')) {
            return ['success' => false, 'error' => 'Plan Limit Reached or Subscription Inactive'];
        }

        $billing = app(\App\Services\BillingService::class);
        $allowed = $billing->recordConversationUsage($this->team, $contact->id, $category, null);

        if (!$allowed) {
            return ['success' => false, 'error' => 'Insufficient Wallet Funds'];
        }

        // --- PRE-PERSISTENCE or USE EXISTING ---
        $conversationService = new \App\Services\ConversationService();
        $conversation = $conversationService->ensureActiveConversation($contact);

        $msg = $existingMessage;
        if (!$msg) {
            $richContent = "Template: {$templateName}";
            $mediaUrl = null;
            $mediaType = null;

            if ($tpl) {
                $bodyComp = collect($tpl->components)->firstWhere('type', 'BODY');
                if ($bodyComp) {
                    $text = $bodyComp['text'] ?? '';
                    foreach ($bodyParams as $index => $param) {
                        $search = '{{' . ($index + 1) . '}}';
                        $text = str_replace($search, $param, $text);
                    }
                    $richContent = $text;
                }
            }

            if (!empty($headerParams)) {
                $headerComp = collect($tpl->components ?? [])->firstWhere('type', 'HEADER');
                if ($headerComp && in_array($headerComp['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    $mediaType = strtolower($headerComp['format']);
                    $mediaUrl = $headerParams[0] ?? null;
                }
            }

            $msg = \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'conversation_id' => $conversation->id,
                'campaign_id' => $campaignId,
                'type' => 'template',
                'direction' => 'outbound',
                'status' => 'queued',
                'content' => $richContent,
                'media_url' => $mediaUrl,
                'media_type' => $mediaType,
                'metadata' => [
                    'template_name' => $templateName,
                    'language' => $language,
                    'variables' => array_merge($headerParams, $bodyParams, $footerParams),
                    'is_bot' => $this->isBot
                ],
            ]);
        }

        try {
            $response = $this->sendRequest('messages', $payload);

            if ($response['success'] ?? false) {
                $wamId = $response['data']['messages'][0]['id'] ?? null;
                $conversationService->handleOutboundMessage($conversation, $this->isBot);

                $msg->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $wamId,
                    'sent_at' => now(),
                ]);
            } else {
                $msg->update([
                    'status' => 'failed',
                    'error_message' => json_encode($response['error'] ?? 'Unknown Error'),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            $msg->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark a message as read.
     */
    public function markRead($messageId)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendRequest('messages', $payload);
    }

    /**
     * Get Business Profile.
     */
    public function getBusinessProfile()
    {
        return $this->sendRequest('whatsapp_business_profile', [
            'fields' => 'about,address,description,email,profile_picture_url,websites,vertical'
        ], 'get');
    }

    /**
     * Update Business Profile.
     */
    public function updateBusinessProfile(array $data)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
        ] + $data;

        return $this->sendRequest('whatsapp_business_profile', $payload, 'post');
    }

    /**
     * Get Templates.
     */
    public function getTemplates()
    {
        $wabaId = $this->team->whatsapp_business_account_id;
        if (!$wabaId) {
            throw new \Exception("WABA ID is not configured.");
        }
        $url = "{$this->baseUrl}/{$wabaId}/message_templates";
        return $this->sendRequestFullUrl($url, 'get');
    }

    /**
     * Create Template.
     */
    public function createTemplate(array $data)
    {
        $wabaId = $this->team->whatsapp_business_account_id;
        if (!$wabaId) {
            throw new \Exception("WABA ID is not configured.");
        }
        $url = "{$this->baseUrl}/{$wabaId}/message_templates";
        return $this->sendRequestFullUrl($url, 'post', $data);
    }

    /**
     * Delete Template.
     */
    public function deleteTemplate($name)
    {
        $wabaId = $this->team->whatsapp_business_account_id;
        if (!$wabaId) {
            throw new \Exception("WABA ID is not configured.");
        }
        $url = "{$this->baseUrl}/{$wabaId}/message_templates";
        return $this->sendRequestFullUrl($url, 'delete', ['name' => $name]);
    }

    protected function sendRequest($endpoint, $data = [], $method = 'post')
    {
        $url = "{$this->baseUrl}/{$this->phoneId}/{$endpoint}";
        return $this->sendRequestFullUrl($url, $method, $data);
    }

    protected function sendRequestFullUrl($url, $method, $data = [])
    {
        $client = Http::withToken($this->token)
            ->withHeaders(['Content-Type' => 'application/json']);

        $response = null;

        Log::debug("WhatsApp API Sending [$method] to $url", ['data' => $data]);

        if ($method === 'post') {
            $response = $client->post($url, $data);
        } elseif ($method === 'get') {
            $response = $client->get($url, $data);
        } elseif ($method === 'delete') {
            $response = $client->delete($url, $data);
        } else {
            return ['success' => false, 'error' => 'Unsupported method'];
        }

        if ($response->failed()) {
            if ($response->status() === 401 && $this->team) {
                $this->team->whatsapp_setup_state = \App\Enums\IntegrationState::SUSPENDED;
                $this->team->save();
            }

            Log::error('WhatsApp API Error', [
                'status' => $response->status(),
                'error' => $response->json(),
                'payload' => $data,
                'url' => $url,
            ]);
            return ['success' => false, 'error' => $response->json(), 'status_code' => $response->status()];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    protected function verifyReadyToSend()
    {
        if (!$this->team)
            return;

        $state = $this->team->whatsapp_setup_state;
        $allowed = [
            \App\Enums\IntegrationState::READY,
            \App\Enums\IntegrationState::READY_WARNING,
            \App\Enums\IntegrationState::ACTIVE
        ];

        if ($state === \App\Enums\IntegrationState::PROVISIONED) {
            if (!empty($this->team->whatsapp_access_token) && !empty($this->team->whatsapp_phone_number_id)) {
                $this->team->whatsapp_setup_state = \App\Enums\IntegrationState::READY;
                $this->team->save();
                return;
            }
        }

        if (!in_array($state, $allowed)) {
            throw new \Exception("Messaging blocked. Connection state: " . ($state ? $state->label() : 'Unknown'));
        }
    }
}
