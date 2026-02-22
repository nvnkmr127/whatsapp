<?php

namespace App\Services;

use App\Models\Team;
use App\Models\CallSettings;
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
            Log::warning("WhatsApp credentials not configured for team: {$team->name}. Token: " . ($this->token ? 'SET' : 'MISSING') . ", PhoneId: " . ($this->phoneId ? 'SET' : 'MISSING'));
        } else {
            Log::debug("WhatsAppService: Credentials set for team {$team->id}. Phone ID: {$this->phoneId}");
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
            // Handle array values to prevent conversion errors
            if (is_array($value)) {
                $value = json_encode($value);
            }

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
     * Normalize phone numbers for WhatsApp calling payloads (digits only).
     */
    protected function normalizeCallNumber(?string $number): ?string
    {
        if (!$number) {
            return null;
        }
        $normalized = preg_replace('/\D+/', '', $number);
        return $normalized ?: null;
    }

    /**
     * Resolve the peer number (customer) for a call.
     */
    protected function getCallPeerNumber(\App\Models\WhatsAppCall $call): ?string
    {
        if ($call->direction === 'inbound') {
            return $call->from_number ?: $call->to_number;
        }
        if ($call->direction === 'outbound') {
            return $call->to_number ?: $call->from_number;
        }
        return $call->to_number ?: $call->from_number;
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

                \App\Events\MessageSent::dispatch($msg);
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

        $mediaObject = [
            'link' => $link,
        ];

        // WhatsApp only supports captions for these types
        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $mediaObject['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => $type,
            $type => $mediaObject
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

                \App\Events\MessageSent::dispatch($msg);
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

                \App\Events\MessageSent::dispatch($msg);
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

                \App\Events\MessageSent::dispatch($msg);
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

        // Handle Authentication Template Buttons (COPY_CODE)
        // Automatically inject the OTP code into the button component if it's a COPY_CODE button
        if ($tpl && !empty($bodyParams)) {
            $tplComponents = $tpl->components ?? [];
            foreach ($tplComponents as $component) {
                if (($component['type'] === 'BUTTONS') && !empty($component['buttons'])) {
                    foreach ($component['buttons'] as $idx => $btn) {
                        if (($btn['type'] ?? '') === 'COPY_CODE') {
                            $components[] = [
                                'type' => 'button',
                                'sub_type' => 'url', // COPY_CODE uses 'url' subtype for parameter injection
                                'index' => (string) $idx,
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => (string) ($bodyParams[0] ?? '') // The code
                                    ]
                                ]
                            ];
                        }
                    }
                }
            }
        }



        // FALLBACK: If we still haven't added a button component, but the template name strongly suggests it's an OTP template
        // (and we have body params which usually implies the code), we blindly add the button param.
        // This fixes cases where local DB sync is partial and doesn't explicitly show the button component.
        $hasButtonParam = collect($components)->contains('type', 'button');
        if (!$hasButtonParam && !empty($bodyParams) && in_array($templateName, ['verification', 'verification_code'])) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => (string) $bodyParams[0]
                    ]
                ]
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

        if (!$policy->canSendTemplate($contact, strtolower($category))) {
            $reason = "Policy Blocked";
            if ($category === 'MARKETING' && $contact->opt_in_status !== 'opted_in') {
                $reason = "CAT_MARKETING_NO_OPT_IN: Marketing message blocked. Contact has not opted-in.";
            } elseif ($contact->opt_in_status === 'opted_out') {
                $reason = "CAT_{$category}_BLOCKED: User has explicitly opted out.";
            }

            return [
                'success' => false,
                'error' => $reason
            ];
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

        // --- STATUS CHECK (Safety Gate) ---
        // Verify template is actually safe to send right now.
        $templateModel = \App\Models\WhatsappTemplate::where('team_id', $this->team->id)
            ->where('name', $templateName)
            ->where('language', $language)
            ->first();

        if ($templateModel) {
            // ... (existing status checks) ...

            // --- COOLDOWN CHECK (Spam Prevention) ---
            $healthService = new \App\Services\TemplateHealthService();
            if (!$healthService->checkCooldown($contact, $templateModel->category)) {
                return ['success' => false, 'error' => "Message blocked by cooldown rules ({$templateModel->category})."];
            }
        }

        // --- SANITIZATION ---
        $tplService = new \App\Services\TemplateService();
        $bodyParams = $tplService->sanitizeVariables($bodyParams);
        $headerParams = $tplService->sanitizeVariables($headerParams);
        $footerParams = $tplService->sanitizeVariables($footerParams);

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

                // Record Usage for Cooldowns
                if (isset($templateModel)) {
                    (new \App\Services\TemplateHealthService)->recordUsage($contact, $templateModel->category);
                }

                \App\Events\MessageSent::dispatch($msg);
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
    /**
     * Get Templates (With Pagination).
     */
    public function getTemplates()
    {
        $wabaId = $this->team->whatsapp_business_account_id;
        if (!$wabaId) {
            throw new \Exception("WABA ID is not configured.");
        }

        $appId = config('whatsapp.app_id');
        $url = "{$this->baseUrl}/{$wabaId}/message_templates";
        $allItems = [];

        do {
            $params = [];
            if ($appId) {
                $params['app_id'] = $appId;
            }

            $response = $this->sendRequestFullUrl($url, 'get', $params);

            if (!($response['success'] ?? false)) {
                return $response; // Return error immediately if any page fails
            }

            $data = $response['data'] ?? [];
            $allItems = array_merge($allItems, $data['data'] ?? []);

            $url = $data['paging']['next'] ?? null;

        } while ($url);

        return ['success' => true, 'data' => $allItems];
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
     * Exchange short-lived user token for long-lived token.
     */
    public function exchangeToken(string $shortLivedToken)
    {
        $appId = config('whatsapp.app_id');
        $appSecret = config('whatsapp.app_secret');

        if (!$appId || !$appSecret) {
            throw new \Exception("Facebook App configuration missing (app_id or app_secret).");
        }

        $url = "https://graph.facebook.com/" . config('whatsapp.api_version', 'v21.0') . "/oauth/access_token";

        $response = Http::get($url, [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            Log::error('WhatsApp Token Exchange Failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return ['success' => false, 'error' => $response->json(), 'status_code' => $response->status()];
        }

        return ['success' => true, 'data' => $response->json()];
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

    /**
     * Upload Media for Template (Resumable Upload).
     */
    public function uploadMediaForTemplate($file)
    {
        $appId = config('whatsapp.app_id');
        if (!$appId) {
            throw new \Exception("Facebook App ID is not configured.");
        }

        // 1. Start Upload Session
        $sessionUrl = "https://graph.facebook.com/" . config('whatsapp.api_version', 'v21.0') . "/{$appId}/uploads";

        $response = Http::post($sessionUrl, [
            'file_length' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'access_token' => $this->team->whatsapp_access_token, // Use team token
        ]);

        if ($response->failed()) {
            throw new \Exception("Failed to start upload session: " . $response->body());
        }

        $sessionId = $response->json()['id'];

        // 2. Upload File Content
        $uploadUrl = "https://graph.facebook.com/" . config('whatsapp.api_version', 'v21.0') . "/{$sessionId}";

        $uploadResponse = Http::withHeaders([
            'Authorization' => 'OAuth ' . $this->team->whatsapp_access_token,
            'file_offset' => 0,
            'Content-Type' => $file->getMimeType(),
        ])->send('POST', $uploadUrl, [
            'body' => fopen($file->getRealPath(), 'r')
        ]);

        if ($uploadResponse->failed()) {
            throw new \Exception("Failed to upload file content: " . $uploadResponse->body());
        }

        // 3. Return Handle
        return $uploadResponse->json()['h'] ?? null;
    }

    /**
     * Get System Call Settings.
     * GET /<PHONE_ID>/settings
     */
    public function getSystemCallSettings()
    {
        if (!$this->phoneId) {
            throw new \Exception("Phone ID is not configured.");
        }

        $url = "{$this->baseUrl}/{$this->phoneId}/settings";
        return $this->sendRequestFullUrl($url, 'get', ['include_sip_credentials' => 'true']);
    }

    /**
     * Disable SIP calling to enable Graph API (WebRTC) calling.
     * When SIP is enabled, Graph API calls for calling features return error 131055/2593026.
     * 
     * @return array API response
     */
    public function disableSipCalling()
    {
        if (!$this->phoneId) {
            throw new \Exception("Phone ID is not configured.");
        }

        Log::info("Disabling SIP calling for phone", [
            'team_id' => $this->team->id,
            'phone_id' => $this->phoneId,
        ]);

        $url = "{$this->baseUrl}/{$this->phoneId}/settings";
        $payload = [
            'sip_status' => 'DISABLED',
        ];

        $response = $this->sendRequestFullUrl($url, 'post', $payload);

        if ($response['success'] ?? false) {
            Log::info("SIP calling disabled successfully", [
                'team_id' => $this->team->id,
                'phone_id' => $this->phoneId,
            ]);
        } else {
            Log::error("Failed to disable SIP calling", [
                'team_id' => $this->team->id,
                'phone_id' => $this->phoneId,
                'error' => $response['error'] ?? 'Unknown error',
            ]);
        }

        return $response;
    }

    /**
     * Update System Call Settings.
     * POST /<PHONE_ID>/settings
     */
    public function updateSystemCallSettings(array $settings)
    {
        if (!$this->phoneId) {
            throw new \Exception("Phone ID is not configured.");
        }

        // Prepare Calling Features payload
        $callingPayload = [];

        // 1. Status (Meta expects ENABLED/DISABLED)
        if (isset($settings['status'])) {
            $callingPayload['status'] = strtoupper($settings['status']);
        }

        // 2. Call Icon Visibility (Meta expects DEFAULT/DISABLE_ALL)
        if (isset($settings['call_icon_visibility'])) {
            $val = strtolower($settings['call_icon_visibility']);
            $callingPayload['call_icon_visibility'] = ($val === 'show' || $val === 'default') ? 'DEFAULT' : 'DISABLE_ALL';
        }

        // 3. Callback Permission (Meta expects ENABLED/DISABLED)
        if (isset($settings['callback_permission_status'])) {
            $callingPayload['callback_permission_status'] = strtoupper($settings['callback_permission_status']);
        }

        // 4. Call Hours / Business Hours
        $weeklyHours = [];
        $timezone = $settings['timezone'] ?? 'UTC';
        $inputHours = null;

        if (isset($settings['business_hours'])) {
            if (isset($settings['business_hours']['hours']) && is_array($settings['business_hours']['hours'])) {
                // Format from CallSettingsController: ['business_hours' => ['hours' => [...], 'timezone' => '...']]
                $inputHours = $settings['business_hours']['hours'];
                $timezone = $settings['business_hours']['timezone'] ?? $timezone;
            } elseif (is_array($settings['business_hours'])) {
                // Keyed format: ['mon' => ['09:00', '17:00']] OR List format: [['day' => 'MON', ...]]
                $inputHours = $settings['business_hours'];
            }
        }

        if ($inputHours) {
            $daysMap = [
                'mon' => 'MONDAY',
                'tue' => 'TUESDAY',
                'wed' => 'WEDNESDAY',
                'thu' => 'THURSDAY',
                'fri' => 'FRIDAY',
                'sat' => 'SATURDAY',
                'sun' => 'SUNDAY',
                'MON' => 'MONDAY',
                'TUE' => 'TUESDAY',
                'WED' => 'WEDNESDAY',
                'THU' => 'THURSDAY',
                'FRI' => 'FRIDAY',
                'SAT' => 'SATURDAY',
                'SUN' => 'SUNDAY'
            ];

            foreach ($inputHours as $key => $val) {
                $day = null;
                $open = null;
                $close = null;

                // Handle keyed format: 'mon' => ['09:00', '17:00']
                if (isset($daysMap[$key]) && is_array($val) && count($val) === 2) {
                    $day = $daysMap[$key];
                    $open = str_replace(':', '', $val[0]);
                    $close = str_replace(':', '', $val[1]);
                }
                // Handle list format: ['day' => 'MON', 'open' => '09:00', 'close' => '17:00']
                elseif (is_array($val) && isset($val['day']) && isset($val['open']) && isset($val['close'])) {
                    $day = $daysMap[$val['day']] ?? $val['day'];
                    $open = str_replace(':', '', $val['open']);
                    $close = str_replace(':', '', $val['close']);
                }

                if ($day && $open && $close) {
                    $weeklyHours[] = [
                        'day_of_week' => $day,
                        'open_time' => $open,
                        'close_time' => $close
                    ];
                }
            }

            if (!empty($weeklyHours)) {
                $callingPayload['call_hours'] = [
                    'status' => 'ENABLED',
                    'timezone_id' => $timezone,
                    'weekly_operating_hours' => $weeklyHours
                ];
            }
        } elseif (isset($settings['call_hours'])) {
            $callingPayload['call_hours'] = $settings['call_hours'];
        }

        Log::debug("WhatsAppService: Preparation for Meta settings sync", [
            'team_id' => $this->team->id,
            'has_weekly_hours' => count($weeklyHours),
            'timezone' => $timezone,
            'payload_keys' => array_keys($callingPayload)
        ]);

        if (empty($callingPayload)) {
            return ['success' => true, 'message' => 'No settings to update'];
        }

        // Meta's settings endpoint expects parameters at the top level, not wrapped in 'calling'
        $payload = $callingPayload;

        $url = "{$this->baseUrl}/{$this->phoneId}/settings";
        return $this->sendRequestFullUrl($url, 'post', $payload);
    }

    /**
     * Request permission to call a contact
     * 
     * @param int $contactId Contact ID
     * @return array Response with permission status
     * @throws \Exception
     */
    public function requestCallPermission(int $contactId): array
    {
        $contact = \App\Models\Contact::findOrFail($contactId);
        $permissionService = new \App\Services\CallPermissionService();

        // Validate permission request
        $validation = $permissionService->validatePermissionRequest($contact, $this->team);

        if (!$validation['allowed']) {
            return $validation;
        }

        // Track the permission request
        $permission = $permissionService->trackPermissionRequest(
            $contact,
            $this->team,
            $this->phoneId
        );

        // Send permission request message via WhatsApp
        // This uses a template message with a call permission button
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $contact->phone,
            'type' => 'template',
            'template' => [
                'name' => 'call_permission_request', // Must be pre-approved template
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'button',
                        'sub_type' => 'quick_reply',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'payload',
                                'payload' => json_encode([
                                    'action' => 'grant_call_permission',
                                    'permission_id' => $permission->id
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->sendRequest('messages', $payload);

            if ($response['success'] ?? false) {
                Log::info('Call permission request sent', [
                    'team_id' => $this->team->id,
                    'contact_id' => $contactId,
                    'permission_id' => $permission->id,
                ]);

                return [
                    'success' => true,
                    'permission' => $permission,
                    'message' => 'Permission request sent successfully',
                ];
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to send call permission request', [
                'team_id' => $this->team->id,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a call link for the configured phone number
     * 
     * @return string WhatsApp call link
     */
    public function generateCallLink(): string
    {
        if (!$this->team->whatsapp_phone_display) {
            // Fallback: try to see if phoneId looks like a phone number (not 15 digits starting with 1/3)
            // But usually ID is different. If missing, we can't generate a valid wa.me link.
            throw new \Exception("Display Phone Number is not configured for this team.");
        }

        // Format: https://wa.me/{phone_number}?call=1
        $phoneNumber = str_replace(['+', ' ', '-'], '', $this->team->whatsapp_phone_display);
        return "https://wa.me/{$phoneNumber}?call=1";
    }

    /**
     * Send a template message with a native "voice_call" button
     * This creates the "Call Button Deep Link" experience directly in chat
     * 
     * @param int $contactId
     * @param string $templateName Name of template with a PHONE_NUMBER button
     */
    public function sendCallButtonMessage(int $contactId, string $templateName = 'call_us_now')
    {
        $contact = \App\Models\Contact::findOrFail($contactId);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $contact->phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'],
                'components' => [
                    // Note: Button component for PHONE_NUMBER type often doesn't need params 
                    // unless the number is dynamic. If static in approved template, no component needed.
                    // If you need to override the number:
                    /*
                   [
                       'type' => 'button',
                       'sub_type' => 'url', // or quick_reply, but phone_number is different
                       'index' => 0,
                       'parameters' => [
                           ['type' => 'text', 'text' => $this->team->whatsapp_phone_display]
                       ]
                   ]
                   */
                ]
            ]
        ];

        return $this->sendRequest('messages', $payload);
    }

    /**
     * Initiate an outbound WhatsApp call with permission validation
     * 
     * @param string $to Phone number to call
     * @param int|null $permissionId Optional permission ID to validate
     * @param array $options Additional call options
     * @return array Response from WhatsApp API
     * @throws \Exception
     */
    public function initiateCallWithPermission(string $to, ?int $permissionId = null, array $options = []): array
    {
        $contact = $this->getOrCreateContact($to);
        $permissionService = new \App\Services\CallPermissionService();

        // If permission ID provided, validate it
        if ($permissionId) {
            $permission = \App\Models\CallPermission::findOrFail($permissionId);

            if (!$permissionService->validateCallingWindow($permission)) {
                throw new \Exception("Call permission has expired or is not valid.");
            }

            // Record the call against this permission
            $permissionService->recordCall($permission);
        }

        // Call the existing initiateCall method
        $response = $this->initiateCall($to, null, $options);

        return $response;
    }

    /**
     * Initiate an outbound WhatsApp call.
     * 
     * @param string $to Phone number to call
     * @param string|null $sdp SDP Offer from the browser (for VOIP)
     * @param array $options Additional options
     * @return array Response from WhatsApp API
     * @throws \Exception
     */
    public function initiateCall(string $to, ?string $sdp = null, array $options = [])
    {
        // Check availability
        if (!$this->team->isWithinBusinessHours()) {
            throw new \Exception("Cannot initiate call outside of business hours.");
        }

        // Check if calling is enabled
        $phoneId = $this->team->whatsapp_phone_number_id;
        $settings = \App\Models\CallSettings::where('team_id', $this->team->id)
            ->where('phone_number_id', $phoneId)
            ->first();

        if (!$settings || !$settings->calling_enabled) {
            throw new \Exception("Calling is not enabled for this team.");
        }

        // Check monthly call limits
        if ($this->team->max_call_minutes_per_month) {
            $currentMonth = now()->format('Y-m');
            $minutesUsed = \App\Models\WhatsAppCall::where('team_id', $this->team->id)
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
                ->sum('duration_seconds') / 60;

            if ($minutesUsed >= $this->team->max_call_minutes_per_month) {
                throw new \Exception("Monthly call limit reached ({$this->team->max_call_minutes_per_month} minutes).");
            }
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeCallNumber($to) ?: $to,
            'action' => 'connect',
            'from' => $phoneId, // Required when using root /calls endpoint
        ];

        if (!empty($options['biz_opaque_callback_data'])) {
            $payload['biz_opaque_callback_data'] = (string) $options['biz_opaque_callback_data'];
        }

        // Add Session (SDP) if provided
        if ($sdp) {
            $sdp = \App\Services\SDPValidator::sanitize($sdp);
            $payload['session'] = [
                'sdp' => $sdp,
                'sdp_type' => 'offer', // We are making the offer
            ];
        }

        try {
            $response = $this->sendRequest('calls', $payload);

            if ($response['success'] ?? false) {
                $callId = $response['data']['id'] ?? $response['data']['calls'][0]['id'] ?? null;

                if (!$callId) {
                    throw new \Exception("WhatsApp API Response missing Call ID: " . json_encode($response['data']));
                }

                // Create call record
                $contact = $this->getOrCreateContact($to);
                $conversationService = new \App\Services\ConversationService();
                $conversation = $conversationService->ensureActiveConversation($contact);

                $call = \App\Models\WhatsAppCall::create([
                    'team_id' => $this->team->id,
                    'contact_id' => $contact->id,
                    'conversation_id' => $conversation->id,
                    'call_id' => $callId,
                    'direction' => 'outbound',
                    'status' => 'initiated',
                    'from_number' => $this->phoneId,
                    'to_number' => $to,
                    'initiated_at' => now(),
                    'metadata' => array_merge($options, [
                        'sdp' => $sdp,
                        'phone_number_id' => $this->phoneId
                    ]),
                ]);

                // Emit CallOffered event
                event(new \App\Events\CallOffered($call));

                // Record for safeguards
                $safeguardService = new \App\Services\CallSafeguardService();
                $safeguardService->recordOutboundCall($this->team);

                Log::info("Outbound call initiated", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                    'to' => $to,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Failed to initiate call", [
                'team_id' => $this->team->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Answer an incoming WhatsApp call.
     * 
     * @param string $callId WhatsApp call identifier
     * @param array|null $session Session data (SDP) for VOIP answering
     * @param string|null $phoneNumberId Optional Phone Number ID override
     * @return array Response from WhatsApp API
     * @throws \Exception
     */
    public function answerCall(string $callId, ?array $session = null, ?string $phoneNumberId = null, ?string $bizOpaqueCallbackData = null)
    {
        $call = \App\Models\WhatsAppCall::where('call_id', $callId)
            ->where('team_id', $this->team->id)
            ->firstOrFail();

        if (!in_array($call->status, ['initiated', 'ringing', 'in_progress'])) {
            throw new \Exception("Call is not in a valid state to answer. Current status: {$call->status}");
        }

        // Validate SDP if provided
        if ($session && isset($session['sdp'])) {
            $validation = \App\Services\SDPValidator::validate($session['sdp']);

            if (!$validation['valid']) {
                $errorMsg = \App\Services\SDPValidator::getValidationSummary($validation);

                Log::error("SDP validation failed", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ]);

                throw new \Exception("Invalid SDP: {$errorMsg}");
            }

            // Log warnings if any
            if (!empty($validation['warnings'])) {
                Log::warning("SDP validation warnings", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                    'warnings' => $validation['warnings'],
                ]);
            }

            // Sanitize SDP
            $session['sdp'] = \App\Services\SDPValidator::sanitize($session['sdp']);

            // Log SDP exchange
            Log::info("SDP answer generated", [
                'team_id' => $this->team->id,
                'call_id' => $callId,
                'codecs' => \App\Services\SDPValidator::extractCodecs($session['sdp']),
            ]);
        }

        if ($session && isset($session['sdp'])) {
            $session['sdp_type'] = 'answer';
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'action' => 'accept',
        ];

        // Only include 'to' for outbound calls (business calling user)
        // Use peer number (customer) for "to"
        $peerNumber = $this->getCallPeerNumber($call);
        if ($peerNumber) {
            $payload['to'] = $this->normalizeCallNumber($peerNumber) ?: $peerNumber;
        }

        if ($session) {
            $payload['session'] = $session;
        }
        if ($bizOpaqueCallbackData) {
            $payload['biz_opaque_callback_data'] = $bizOpaqueCallbackData;
        } elseif (!empty($call->metadata['biz_opaque_callback_data'])) {
            $payload['biz_opaque_callback_data'] = $call->metadata['biz_opaque_callback_data'];
        }

        $phoneIdToUse = $phoneNumberId ?: $this->phoneId;
        if (!$phoneIdToUse) {
            throw new \Exception("Phone ID is not configured.");
        }

        // Retry logic with exponential backoff
        $maxRetries = 2;
        $retryDelay = 500; // milliseconds
        $lastException = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $startTime = microtime(true);

                $payload['call_id'] = $callId;
                $response = $this->sendRequest('calls', $payload, 'post');

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($response['success'] ?? false) {
                    $call->markAsAnswered();

                    // Update metadata with timing info
                    $metadata = $call->metadata;
                    if (!is_array($metadata)) {
                        $metadata = [];
                    }
                    $metadata['answer_sent_at'] = now()->toIso8601String();
                    $metadata['response_time_ms'] = $responseTime;
                    $metadata['retry_attempts'] = $attempt;
                    $call->update(['metadata' => $metadata]);

                    // Dispatch event for real-time UI updates
                    event(new \App\Events\CallAnswered($call));

                    Log::info("Call answered successfully", [
                        'team_id' => $this->team->id,
                        'call_id' => $callId,
                        'response_time_ms' => $responseTime,
                        'attempts' => $attempt + 1,
                    ]);

                    return $response;
                }

                // If not successful but no exception, log and retry
                Log::warning("Call answer response not successful", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                    'response' => $response,
                    'attempt' => $attempt + 1,
                ]);

                if ($attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2; // Exponential backoff
                }

            } catch (\Exception $e) {
                $lastException = $e;

                Log::error("Failed to answer call (attempt " . ($attempt + 1) . ")", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                // Don't retry on validation errors
                if (strpos($e->getMessage(), 'Invalid SDP') !== false) {
                    throw $e;
                }

                if ($attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2; // Exponential backoff
                } else {
                    throw $e;
                }
            }
        }

        // If we get here, all retries failed
        throw $lastException ?? new \Exception("Failed to answer call after {$maxRetries} retries");
    }

    /**
     * Pre-accept an incoming WhatsApp call (optional step).
     *
     * @param string $callId WhatsApp call identifier
     * @param array|null $session Session data (SDP) for VOIP pre-accept
     * @param string|null $phoneNumberId Optional Phone Number ID override
     * @param string|null $bizOpaqueCallbackData Optional opaque callback data
     * @return array
     * @throws \Exception
     */
    public function preAcceptCall(string $callId, ?array $session = null, ?string $phoneNumberId = null, ?string $bizOpaqueCallbackData = null)
    {
        $call = \App\Models\WhatsAppCall::where('call_id', $callId)
            ->where('team_id', $this->team->id)
            ->firstOrFail();

        if (!in_array($call->status, ['initiated', 'ringing', 'in_progress'])) {
            throw new \Exception("Call is not in a valid state to pre-accept. Current status: {$call->status}");
        }

        if ($session && isset($session['sdp'])) {
            $validation = \App\Services\SDPValidator::validate($session['sdp']);
            if (!$validation['valid']) {
                $errorMsg = \App\Services\SDPValidator::getValidationSummary($validation);
                throw new \Exception("Invalid SDP: {$errorMsg}");
            }
            $session['sdp'] = \App\Services\SDPValidator::sanitize($session['sdp']);
            $session['sdp_type'] = 'answer';
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'action' => 'pre_accept',
            'call_id' => $callId,
        ];
        // Use peer number (customer) for "to"
        $peerNumber = $this->getCallPeerNumber($call);
        if ($peerNumber) {
            $payload['to'] = $this->normalizeCallNumber($peerNumber) ?: $peerNumber;
        }
        if ($session) {
            $payload['session'] = $session;
        }
        if ($bizOpaqueCallbackData) {
            $payload['biz_opaque_callback_data'] = $bizOpaqueCallbackData;
        } elseif (!empty($call->metadata['biz_opaque_callback_data'])) {
            $payload['biz_opaque_callback_data'] = $call->metadata['biz_opaque_callback_data'];
        }

        $phoneIdToUse = $phoneNumberId ?: $this->phoneId;
        if (!$phoneIdToUse) {
            throw new \Exception("Phone ID is not configured.");
        }

        return $this->sendRequest('calls', $payload, 'post');
    }

    /**
     * Reject an incoming WhatsApp call.
     * 
     * @param string $callId WhatsApp call identifier
     * @return array Response from WhatsApp API
     */
    public function rejectCall(string $callId)
    {
        $call = \App\Models\WhatsAppCall::where('call_id', $callId)
            ->where('team_id', $this->team->id)
            ->firstOrFail();

        if ($call->direction === 'outbound') {
            throw new \Exception("Cannot reject an outbound call. Use endCall() instead.");
        }

        if (!in_array($call->status, ['ringing', 'initiated'])) {
            throw new \Exception("Call cannot be rejected. Current status: {$call->status}");
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'action' => 'reject',
            'call_id' => $callId,
        ];
        // Use peer number (customer) for "to"
        $peerNumber = $this->getCallPeerNumber($call);
        if ($peerNumber) {
            $payload['to'] = $this->normalizeCallNumber($peerNumber) ?: $peerNumber;
        }
        if (!empty($call->metadata['biz_opaque_callback_data'])) {
            $payload['biz_opaque_callback_data'] = $call->metadata['biz_opaque_callback_data'];
        }

        try {
            $response = $this->sendRequest('calls', $payload, 'post');

            if ($response['success'] ?? false) {
                $call->markAsRejected();

                // Dispatch event for real-time UI updates
                event(new \App\Events\CallRejected($call));

                Log::info("Call rejected", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Failed to reject call", [
                'team_id' => $this->team->id,
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * End an active WhatsApp call.
     * 
     * @param string $callId WhatsApp call identifier
     * @return array Response from WhatsApp API
     */
    public function endCall(string $callId)
    {
        $call = \App\Models\WhatsAppCall::where('call_id', $callId)
            ->where('team_id', $this->team->id)
            ->firstOrFail();

        if (!$call->isActive()) {
            throw new \Exception("Call is not active. Current status: {$call->status}");
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'action' => 'terminate',
            'call_id' => $callId,
        ];
        // Use peer number (customer) for "to"
        $peerNumber = $this->getCallPeerNumber($call);
        if ($peerNumber) {
            $payload['to'] = $this->normalizeCallNumber($peerNumber) ?: $peerNumber;
        }
        if (!empty($call->metadata['biz_opaque_callback_data'])) {
            $payload['biz_opaque_callback_data'] = $call->metadata['biz_opaque_callback_data'];
        }

        try {
            $response = $this->sendRequest('calls', $payload, 'post');

            if ($response['success'] ?? false) {
                $call->markAsEnded();

                // Dispatch event for real-time UI updates
                event(new \App\Events\CallEnded($call));

                Log::info("Call ended", [
                    'team_id' => $this->team->id,
                    'call_id' => $callId,
                    'duration' => $call->duration_seconds,
                    'cost' => $call->cost_amount,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Failed to end call", [
                'team_id' => $this->team->id,
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the status of a WhatsApp call.
     * 
     * @param string $callId WhatsApp call identifier
     * @return array Call status information
     */
    public function getCallStatus(string $callId)
    {
        $call = \App\Models\WhatsAppCall::where('call_id', $callId)
            ->where('team_id', $this->team->id)
            ->firstOrFail();

        try {
            // Note: WhatsApp Cloud API v21.0 does not currently support direct polling for call status via /calls/{id}.
            // Real-time status updates are delivered via webhooks.
            /*
            $response = $this->sendRequest("calls/{$callId}", [], 'get');

            if ($response['success'] ?? false) {
                // Update local call record with latest status from WhatsApp
                $remoteStatus = $response['data']['status'] ?? null;
                if ($remoteStatus && $remoteStatus !== $call->status) {
                    $call->update(['status' => $remoteStatus]);
                }
            }
            */

            return [
                'success' => true,
                'data' => [
                    'call_id' => $call->call_id,
                    'status' => $call->status,
                    'direction' => $call->direction,
                    'duration_seconds' => $call->duration_seconds,
                    'cost_amount' => $call->cost_amount,
                    'initiated_at' => $call->initiated_at,
                    'answered_at' => $call->answered_at,
                    'ended_at' => $call->ended_at,
                ],
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get call status", [
                'team_id' => $this->team->id,
                'call_id' => $callId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch WhatsApp Business Account Analytics from Meta.
     * GET /<WABA_ID>/analytics
     * 
     * @param string|int $start Date string (Y-m-d) or unix timestamp
     * @param string|int $end Date string (Y-m-d) or unix timestamp
     * @param string $granularity 'DAILY' | 'MONTHLY' | 'HALF_HOUR'
     * @param array $metrics ['conversation_analytics', 'messaging_analytics', 'cost']
     * @return array
     */
    public function getAnalytics($start, $end, $granularity = 'DAILY', $metrics = ['conversation_analytics', 'messaging_analytics'])
    {
        $wabaId = $this->team->whatsapp_business_account_id;
        if (!$wabaId) {
            throw new \Exception("WABA ID is not configured.");
        }

        // Convert dates to unix timestamp if they are strings
        if (!is_numeric($start))
            $start = strtotime($start);
        if (!is_numeric($end))
            $end = strtotime($end);

        // For v16.0+, /analytics end point is deprecated.
        // We must query 'conversation_analytics' field on the WABA node.

        $fields = [];
        // We construct the complex field query for conversation_analytics
        // Syntax: conversation_analytics.start(timestamp).end(timestamp).granularity(DAILY)
        $fields[] = "conversation_analytics.start({$start}).end({$end}).granularity({$granularity})";

        $queryParams = [
            'fields' => implode(',', $fields)
        ];

        // URL: https://graph.facebook.com/<API_VERSION>/<WABA_ID>
        $url = "{$this->baseUrl}/{$wabaId}";

        return $this->sendRequestFullUrl($url, 'get', $queryParams, 'analytics');
    }

    protected function sendRequest($endpoint, $data = [], $method = 'post')
    {
        $url = "{$this->baseUrl}/{$this->phoneId}/{$endpoint}";
        return $this->sendRequestFullUrl($url, $method, $data, $endpoint);
    }

    protected function sendRequestFullUrl($url, $method, $data = [], $endpoint = 'unknown')
    {
        $client = Http::withToken($this->token)
            ->withHeaders(['Content-Type' => 'application/json']);

        $response = null;
        $method = strtolower($method);

        Log::debug("WhatsApp API Sending [$method] to $url", ['data' => $data]);

        try {
            if ($method === 'post') {
                $response = $client->post($url, $data);
            } elseif ($method === 'get') {
                $response = $client->get($url, $data);
            } elseif ($method === 'delete') {
                $response = $client->delete($url, $data);
            } else {
                return ['success' => false, 'error' => 'Unsupported method: ' . $method];
            }

            if ($response->failed()) {
                if ($response->status() === 401 && $this->team) {
                    $this->team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::SUSPENDED]);
                    \App\Services\WhatsAppEventBridge::logInteraction($this->team, $endpoint, 'critical', $data, ['error' => $response->json()]);
                } elseif ($this->team) {
                    \App\Services\WhatsAppEventBridge::logInteraction($this->team, $endpoint, 'failed', $data, ['error' => $response->json()]);
                }

                Log::error('WhatsApp API Error', [
                    'status' => $response->status(),
                    'error' => $response->json(),
                    'payload' => $data,
                    'url' => $url,
                ]);
                return [
                    'success' => false,
                    'error' => $response->json(),
                    'status_code' => $response->status(),
                    'message' => $response->json()['error']['message'] ?? 'Unknown API Error'
                ];
            }

            if ($this->team) {
                \App\Services\WhatsAppEventBridge::logInteraction($this->team, $endpoint, 'success', $data, ['url' => $url]);
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            Log::error('WhatsApp API Exception', [
                'message' => $e->getMessage(),
                'url' => $url,
                'method' => $method
            ]);
            return ['success' => false, 'error' => $e->getMessage(), 'status_code' => 500];
        }
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
            Log::warning("Messaging blocked for team {$this->team->id}. Current state: " . ($state ? $state->label() : 'Unknown'));
            throw new \Exception("Messaging blocked. Connection state: " . ($state ? $state->label() : 'Unknown'));
        }
        Log::debug("WhatsAppService: Team {$this->team->id} is ready to send messages.");
    }
    /**
     * Sync business hours to WhatsApp Business Profile.
     */
    public function syncBusinessProfileHours(array $hoursConfig, string $timezone)
    {
        $config = [];
        $daysMap = [
            'mon' => 'MONDAY',
            'tue' => 'TUESDAY',
            'wed' => 'WEDNESDAY',
            'thu' => 'THURSDAY',
            'fri' => 'FRIDAY',
            'sat' => 'SATURDAY',
            'sun' => 'SUNDAY',
            'MON' => 'MONDAY',
            'TUE' => 'TUESDAY',
            'WED' => 'WEDNESDAY',
            'THU' => 'THURSDAY',
            'FRI' => 'FRIDAY',
            'SAT' => 'SATURDAY',
            'SUN' => 'SUNDAY'
        ];

        foreach ($hoursConfig as $h) {
            // Support both formats: object/array keyed by 'day'
            $dayKey = $h['day'] ?? null;
            $open = $h['open'] ?? null;
            $close = $h['close'] ?? null;

            if ($dayKey && isset($daysMap[$dayKey])) {
                $config[] = [
                    'day_of_week' => $daysMap[$dayKey],
                    'mode' => 'OPEN_FOR_BUSINESS',
                    'open_time' => $open,
                    'close_time' => $close,
                ];
            }
        }

        $payload = [
            'business_hours' => [
                'timezone_id' => $timezone,
                'config' => $config
            ]
        ];

        return $this->updateBusinessProfile($payload);
    }
}
