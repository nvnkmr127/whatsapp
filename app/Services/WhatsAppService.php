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

    public function __construct(Team $team = null)
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
     * Send a plain text message.
     */
    public function sendText($to, $message)
    {
        // Find contact to check policy
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $this->team->id, 'phone_number' => $to]
        );

        // Enforce 24h Policy for Free Text
        $policy = new PolicyService();
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked free message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send free text message. 24-hour window is closed or User opted out. (Policy UC-03). Please use a Template.");
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $response = $this->sendRequest('messages', $payload);

        if ($response['success'] ?? false) {
            $wamId = $response['data']['messages'][0]['id'] ?? null;

            // Persist to Database
            \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'conversation_id' => null, // Will be linked by webhook or we can try to find open convo
                'type' => 'text',
                'direction' => 'outbound',
                'status' => 'sent',
                'whatsapp_message_id' => $wamId,
                'content' => $message,
                'sent_at' => now(),
            ]);
        }

        return $response;
    }

    /**
     * Send a media message (Image, Video, Audio, Document).
     */
    public function sendMedia($to, $type, $link, $caption = null)
    {
        // Find contact to check policy
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $this->team->id, 'phone_number' => $to]
        );

        // Enforce 24h Policy for Free Messages (Media is a free message)
        $policy = new PolicyService();
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked media message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send media. 24-hour window is closed or User opted out. Please use a Template.");
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

        $response = $this->sendRequest('messages', $payload);

        if ($response['success'] ?? false) {
            $wamId = $response['data']['messages'][0]['id'] ?? null;

            // Persist to Database
            \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'type' => $type,
                'direction' => 'outbound',
                'status' => 'sent',
                'whatsapp_message_id' => $wamId,
                'media_type' => $type,
                'media_url' => $link, // Store original link or handle accordingly
                'caption' => $caption,
                'sent_at' => now(),
            ]);
        }

        return $response;
    }

    /**
     * Send interactive buttons.
     */
    public function sendInteractiveButtons($to, $text, array $buttons)
    {
        // Find contact to check policy
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $this->team->id, 'phone_number' => $to]
        );

        // Enforce 24h Policy for Free Messages (Interactive is a free message)
        $policy = new PolicyService();
        if ($contact && !$policy->canSendFreeMessage($contact)) {
            Log::warning("Blocked interactive message to {$to}. 24h Window Closed or Opt-out.");
            throw new \Exception("Cannot send interactive buttons. 24-hour window is closed or User opted out. Please use a Template.");
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

        $response = $this->sendRequest('messages', $payload);

        if ($response['success'] ?? false) {
            $wamId = $response['data']['messages'][0]['id'] ?? null;

            // Persist to Database
            \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'type' => 'interactive',
                'direction' => 'outbound',
                'status' => 'sent',
                'whatsapp_message_id' => $wamId,
                'content' => $text, // Main body text
                'metadata' => ['buttons' => $buttons],
                'sent_at' => now(),
            ]);
        }

        return $response;
    }

    /**
     * Send a template message.
     */
    /**
     * Send a template message.
     */
    public function sendTemplate($to, $templateName, $language = 'en_US', $bodyParams = [], $headerParams = [], $footerParams = [])
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
        } elseif ($tpl && !empty($tpl->components)) {
            // BACKWARD COMPATIBILITY: Auto-detect header if not provided
            $headerComponent = collect($tpl->components)->firstWhere('type', 'HEADER');
            if ($headerComponent && in_array($headerComponent['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $format = $headerComponent['format'];
                $mediaLink = array_shift($bodyParams) ?? 'https://via.placeholder.com/150';

                if (!filter_var($mediaLink, FILTER_VALIDATE_URL)) {
                    Log::warning("Expected URL for {$format} header. Using placeholder.");
                    $mediaLink = 'https://placehold.co/600x400.png?text=Image';
                }

                $manualType = strtolower($format);
                $components[] = [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => $manualType,
                            $manualType => ['link' => $mediaLink]
                        ]
                    ]
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

        $category = $tpl ? strtolower($tpl->category) : 'marketing';

        // 3. Resolve Contact ID
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $this->team->id, 'phone_number' => $to]
        );

        // --- POLICY CHECK ---
        $policy = new PolicyService();
        if (!$policy->canSendTemplate($contact, $category)) {
            Log::warning("Blocked template to {$to}. Marketing Opt-in required or User Opted-out.");
            return ['success' => false, 'error' => 'Blocked by Policy: Marketing requires Opt-in, or User Opted-out.'];
        }
        // --------------------

        // 4. Validate Variables
        if ($tpl) {
            $tplService = new TemplateService();
            $allVars = array_merge($headerParams, $bodyParams, $footerParams);
            if (!$tplService->validateVariables($tpl, $allVars)) {
                Log::error("Template Validation Failed for {$to}", ['template' => $templateName, 'vars' => $allVars]);
                return ['success' => false, 'error' => 'Template Variable Mismatch'];
            }
        }

        // 5. Check Wallet & Plan Limits
        $billing = new \App\Services\BillingService();
        $allowed = $billing->recordConversationUsage($this->team, $contact->id, $category, null);

        if (!$allowed) {
            Log::warning("Blocked message to {$to} due to Limits or Funds.");
            return ['success' => false, 'error' => 'Plan Limit Reached or Insufficient Funds'];
        }
        // ---------------------

        $response = $this->sendRequest('messages', $payload);

        if ($response['success'] ?? false) {
            $wamId = $response['data']['messages'][0]['id'] ?? null;

            // Render Body for Display
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

            // Capture Header Media
            if (!empty($headerParams)) {
                $headerComp = collect($tpl->components ?? [])->firstWhere('type', 'HEADER');
                if ($headerComp && in_array($headerComp['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                    $mediaType = strtolower($headerComp['format']);
                    $mediaUrl = $headerParams[0] ?? null; // Assume 1st param is URL
                }
            }

            // Persist to Database
            \App\Models\Message::create([
                'team_id' => $this->team->id,
                'contact_id' => $contact->id,
                'type' => 'template',
                'direction' => 'outbound',
                'status' => 'sent',
                'whatsapp_message_id' => $wamId,
                'content' => $richContent,
                'media_url' => $mediaUrl,
                'media_type' => $mediaType,
                'metadata' => [
                    'template_name' => $templateName,
                    'language' => $language,
                    'variables' => array_merge($headerParams, $bodyParams, $footerParams)
                ],
                'sent_at' => now(),
            ]);
        }

        return $response;
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
     * Get Business Profile (Address, Email, Website, etc.)
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
        // $data can include about, address, description, email, etc.
        $payload = [
            'messaging_product' => 'whatsapp',
        ] + $data;

        return $this->sendRequest('whatsapp_business_profile', $payload, 'post');
    }

    /**
     * Get Templates for the WABA.
     * Note: Templates are stored at WABA level, not Phone ID level.
     */
    public function getTemplates()
    {
        // We use the WABA ID stored in the team settings
        $wabaId = $this->team->whatsapp_business_account_id;

        if (!$wabaId) {
            throw new \Exception("WhatsApp Business Account ID (WABA) is not configured.");
        }

        $url = "{$this->baseUrl}/{$wabaId}/message_templates";
        return $this->sendRequestFullUrl($url, 'get');
    }

    /**
     * Create a new Template.
     */
    public function createTemplate(array $data)
    {
        $wabaId = $this->team->whatsapp_business_account_id;

        if (!$wabaId) {
            throw new \Exception("WhatsApp Business Account ID (WABA) is not configured.");
        }

        $url = "{$this->baseUrl}/{$wabaId}/message_templates";
        return $this->sendRequestFullUrl($url, 'post', $data);
    }

    /**
     * Delete a Template by Name.
     */
    public function deleteTemplate($name)
    {
        $wabaId = $this->team->whatsapp_business_account_id;

        if (!$wabaId) {
            throw new \Exception("WhatsApp Business Account ID (WABA) is not configured.");
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

        if ($method === 'post') {
            $response = $client->post($url, $data);
        } elseif ($method === 'get') {
            $response = $client->get($url, $data);
        } elseif ($method === 'delete') {
            $response = $client->delete($url, $data);
        } else {
            Log::error('Unsupported HTTP method', ['method' => $method]);
            return ['success' => false, 'error' => 'Unsupported HTTP method'];
        }

        if ($response->failed()) {
            Log::error('WhatsApp API Error', [
                'error' => $response->json(),
                'payload' => $data,
                'url' => $url,
            ]);
            // Return validation errors if present
            return ['success' => false, 'error' => $response->json()];
        }


        return ['success' => true, 'data' => $response->json()];
    }
}
