<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl = 'https://graph.facebook.com/v21.0';
    protected $token;
    protected $phoneId;
    protected $team;

    /**
     * Set the context for a specific team.
     */
    public function setTeam(Team $team)
    {
        $this->team = $team; // Store reference
        $this->token = (string) $team->whatsapp_access_token;
        $this->phoneId = $team->whatsapp_phone_number_id;

        if (!$this->token || !$this->phoneId) {
            throw new \Exception("WhatsApp credentials not configured for team: {$team->name}");
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
        $contact = \App\Models\Contact::where('phone_number', $to)
            ->where('team_id', $this->team->id ?? null)
            ->first();

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

        return $this->sendRequest('messages', $payload);
    }

    /**
     * Send interactive buttons.
     */
    public function sendInteractiveButtons($to, $text, array $buttons)
    {
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

        return $this->sendRequest('messages', $payload);
    }

    /**
     * Send a template message.
     */
    /**
     * Send a template message.
     */
    public function sendTemplate($to, $templateName, $language = 'en_US', $variables = [])
    {
        // Fetch Template first to understand structure
        $tpl = \App\Models\WhatsAppTemplate::where('team_id', $this->team->id)
            ->where('name', $templateName)
            ->where('language', $language)
            ->first();

        // Language Fallback
        if (!$tpl) {
            $fallback = \App\Models\WhatsAppTemplate::where('team_id', $this->team->id)
                ->where('name', $templateName)
                ->where('language', 'en_US')
                ->first();
            if ($fallback) {
                $tpl = $fallback;
                $language = 'en_US';
            }
        }

        // Logic to split variables into Header vs Body
        // If template has Header params (IMAGE, VIDEO, DOCUMENT), we assume the FIRST variable is the media link.
        // This is a heuristic until we have a proper UI map.
        $components = [];
        $headerVars = null;
        $bodyVars = $variables;

        if ($tpl && !empty($tpl->components)) {
            $componentData = $tpl->components; // Encrypted/Json? It's cast to array in model.

            // Check for HEADER with format IMAGE/VIDEO/DOCUMENT
            $headerComponent = collect($componentData)->firstWhere('type', 'HEADER');
            if ($headerComponent && in_array($headerComponent['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $format = $headerComponent['format'];
                $mediaLink = array_shift($bodyVars) ?? 'https://via.placeholder.com/150'; // Shift first var as media, or fallback

                // If the shifted var is NOT a URL, put it back? No, valid variables are strings. 
                // We will assume logic: If Media Header exists, Var1 = MediaURL.

                if (!filter_var($mediaLink, FILTER_VALIDATE_URL)) {
                    // Log warning, use fallback
                    Log::warning("Expected URL for {$format} header, got '{$mediaLink}'. Using placeholder.");
                    // Depending on strictness, we might put it back to body if we think logic is wrong.
                    // But for 'channel_partnerv2' which failed, it expects IMAGE.
                    $mediaLink = 'https://placehold.co/600x400.png?text=Image';
                    array_unshift($bodyVars, $variables[0]); // Put original back to body if it wasn't a URL? 
                    // Actually, if the user didn't provide a URL, they probably provided body text.
                }

                $manualType = strtolower($format); // image, video, document
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
        if (!empty($bodyVars)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $this->formatTemplateVariables($bodyVars)[0]['parameters']
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
            if (!$tplService->validateVariables($tpl, $variables)) {
                Log::error("Template Validation Failed for {$to}", ['template' => $templateName, 'vars' => $variables]);
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

        return $this->sendRequest('messages', $payload);
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
        return $this->sendRequest('whatsapp_business_profile?fields=about,address,description,email,profile_picture_url,websites,vertical', [], 'get');
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
