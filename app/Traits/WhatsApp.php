<?php

namespace App\Traits;

use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WhatsApp
{
    protected static string $facebookAPI = 'https://graph.facebook.com/';

    protected static function getApiVersion(): string
    {
        return config('whatsapp.api_version', 'v21.0');
    }

    protected static function getBaseUrl(): string
    {
        return self::$facebookAPI . self::getApiVersion() . '/';
    }

    private function getToken(): string
    {
        return $this->team->whatsapp_access_token ?? auth()->user()->currentTeam->whatsapp_access_token ?? '';
    }

    private function getAccountID(): string
    {
        return $this->team->whatsapp_business_account_id ?? auth()->user()->currentTeam->whatsapp_business_account_id ?? '';
    }

    public function loadTemplatesFromWhatsApp(): array
    {
        try {
            // Mocking or fetching settings
            $accountId = $this->getAccountID();
            $token = $this->getToken();

            if (empty($accountId) || empty($token)) {
                // Return empty or throw generic if not configured
                // But for MVP, we might want to fail gracefully.
            }

            $appId = config('whatsapp.app_id') ?? config('services.facebook.client_id');
            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');

            // System Tokens (prefix EAAB...) usually carry their own app context 
            // and fail with "Provide valid app ID" if app_id is explicitly passed.
            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $url = self::getBaseUrl() . "{$accountId}"; // No trailing slash

            $params = [
                'fields' => 'id,name,message_templates,phone_numbers',
                'access_token' => $token,
            ];

            // Only add appsecret_proof if we have a secret and it's NOT a system token
            // This is safer than app_id which often causes the #200 error
            if (!$isSystemToken && $appSecret) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            Log::debug("WhatsApp Trait: Syncing templates", [
                'waba_id' => $accountId,
                'is_system_token' => $isSystemToken,
                'url' => $url
            ]);

            $response = Http::get($url, $params);

            // Retry without appsecret_proof if it fails with specific error
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {

                    Log::warning("WhatsApp Trait: AppSecret Proof failed, retrying without proof.");
                    unset($params['appsecret_proof']);
                    $response = Http::get($url, $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                Log::error("WhatsApp Trait: API Call Failed", [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'waba_id' => $accountId,
                    'app_id_sent' => $appId
                ]);

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    throw new \Exception("Configuration Error: The Access Token belongs to a different App ID than the one currently configured ($appId). Please regenerate your System User Token for this App ID.");
                }

                throw new \Exception($errorMessage ?? 'API Error ' . $response->status());
            }

            $messageTemplates = $response->json('message_templates.data');
            if (!$messageTemplates) {
                return ['status' => false, 'message' => 'No templates found.'];
            }

            $existingTemplateIds = WhatsappTemplate::pluck('whatsapp_template_id')->toArray();
            $apiTemplateIds = [];

            foreach ($messageTemplates as $templateData) {
                $apiTemplateIds[] = $templateData['id'];

                // Simplify component logic for immediate port
                $components = [];
                $headerParamsCount = $bodyParamsCount = $footerParamsCount = 0;

                foreach ($templateData['components'] as $component) {
                    if ($component['type'] === 'HEADER') {
                        $components['HEADER'] = $component['text'] ?? ($component['format'] ?? '');
                        if (isset($component['text'])) {
                            $headerParamsCount = preg_match_all('/{{(.*?)}}/i', $component['text'], $matches);
                        }
                    }
                    if ($component['type'] === 'BODY') {
                        $components['BODY'] = $component['text'];
                        $bodyParamsCount = preg_match_all('/{{(.*?)}}/i', $component['text'], $matches);
                    }
                    if ($component['type'] === 'FOOTER') {
                        $components['FOOTER'] = $component['text'];
                        $footerParamsCount = preg_match_all('/{{(.*?)}}/i', $component['text'], $matches);
                    }
                }

                WhatsappTemplate::updateOrCreate(
                    ['whatsapp_template_id' => $templateData['id']],
                    [
                        'name' => $templateData['name'], // Field is 'name' in DB, 'template_name' was wrong in code? No, DB has 'name'. check update array
                        'language' => $templateData['language'],
                        'status' => $templateData['status'],
                        'category' => $templateData['category'],
                        // DB has 'components' json column, but code tries mapping unrelated fields.
                        // Migration shows: table->json('components');
                        // Code tries: header_data_text, body_data... those columns DO NOT EXIST in migration!
                        // MAJOR MISMATCH detected between Trait and Migration.
                        // I must also fix the update array to match migration columns.
                        'components' => $templateData['components'], // Save raw components as JSON
                        'team_id' => $this->team->id ?? auth()->user()->currentTeam->id, // Use contextual team
                    ]
                );
            }

            $phoneNumbers = $response->json('phone_numbers.data');

            return [
                'status' => true,
                'message' => 'Templates synced successfully',
                'count' => count($apiTemplateIds),
                'phone_numbers' => $phoneNumbers ?? []
            ];

        } catch (\Throwable $e) {
            Log::error("WhatsApp Template Sync Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function getPhoneNumberDetails(string $phoneNumberId): array
    {
        try {
            $token = $this->getToken();
            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');
            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $params = [
                'fields' => 'display_phone_number,verified_name,quality_rating,messaging_limit_tier',
                'access_token' => $token,
            ];

            if (!$isSystemToken && $appSecret) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            $response = Http::get(self::getBaseUrl() . "{$phoneNumberId}", $params);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured. Please regenerate your System User Token."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'API Error'];
            }

            $data = $response->json();

            return [
                'status' => true,
                'data' => [
                    'display_phone_number' => $data['display_phone_number'] ?? null,
                    'verified_name' => $data['verified_name'] ?? null,
                    'quality_rating' => $data['quality_rating'] ?? null,
                    'messaging_limit_tier' => $data['messaging_limit_tier'] ?? null,
                ]
            ];

        } catch (\Throwable $e) {
            Log::error("WhatsApp Phone Details Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function registerPhone(string $phoneNumberId, string $pin): array
    {
        try {
            $token = $this->getToken();

            if (empty($token)) {
                return ['status' => false, 'message' => 'Access token not configured.'];
            }

            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');
            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $url = self::getBaseUrl() . "{$phoneNumberId}/register";

            $params = [
                'messaging_product' => 'whatsapp',
                'pin' => $pin
            ];

            if (!$isSystemToken && $appSecret) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            Log::debug("WhatsApp Trait: Registering phone", [
                'phone_id' => $phoneNumberId,
                'is_system_token' => $isSystemToken
            ]);

            $response = Http::withToken($token)->post($url, $params);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured. Please regenerate your System User Token."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'Registration Failed'];
            }

            return ['status' => true, 'message' => 'Phone number registered successfully'];

        } catch (\Throwable $e) {
            Log::error("WhatsApp Register Phone Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Exchange a short-lived user token for a long-lived (60 days) access token.
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        try {
            $appId = config('whatsapp.app_id');
            $appSecret = config('whatsapp.app_secret');

            if (empty($appId) || empty($appSecret)) {
                Log::error("WhatsApp Trait: config(whatsapp.app_id) or app_secret missing during exchange.");
                return ['status' => false, 'message' => 'Facebook App ID or Secret not configured in whatsapp.php'];
            }

            Log::debug("WhatsApp Trait: Exchanging token", [
                'app_id' => $appId,
                'token_prefix' => substr($shortLivedToken, 0, 8) . '...'
            ]);

            $response = Http::get(self::$facebookAPI . 'oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                Log::error("WhatsApp Trait: Token Exchange Failed", [
                    'status' => $response->status(),
                    'error' => $errorData
                ]);

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured ($appId). Please regenerate your System User Token for this App ID."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'Token exchange failed'];
            }

            return [
                'status' => true,
                'access_token' => $response->json('access_token'),
                'expires_in' => $response->json('expires_in'), // Usually present for user tokens
            ];
        } catch (\Throwable $e) {
            Log::error("WhatsApp Token Exchange Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Subscribe the application to the WhatsApp Business Account's webhooks.
     */
    public function subscribeToWebhooks(string $wabaId, string $token): array
    {
        try {
            $url = self::getBaseUrl() . "{$wabaId}/subscribed_apps";
            $appId = config('whatsapp.app_id') ?? config('services.facebook.client_id');
            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');

            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $params = ['app_id' => $appId];
            if (!$isSystemToken) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            Log::debug("WhatsApp Trait: Subscribing to webhooks", ['waba_id' => $wabaId, 'is_system_token' => $isSystemToken]);

            $response = Http::withToken($token)->post($url, $params);

            // Retry without appsecret_proof if it fails with specific error
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {

                    Log::warning("WhatsApp Trait: AppSecret Proof failed for Webhook Subscribe, retrying without proof.");
                    unset($params['appsecret_proof']);
                    $response = Http::withToken($token)->post($url, $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                Log::error("WhatsApp Trait: Webhook Subscription Failed", ['status' => $response->status(), 'error' => $errorData]);

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured ($appId). Please regenerate your System User Token for this App ID."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'Webhook subscription failed'];
            }

            return ['status' => true, 'message' => 'Successfully subscribed to webhooks'];
        } catch (\Throwable $e) {
            Log::error("WhatsApp Webhook Subscription Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function debugToken(string $token): array
    {
        try {
            $appId = config('whatsapp.app_id');
            $appSecret = config('whatsapp.app_secret');
            $appToken = $appId . '|' . $appSecret;

            Log::debug("WhatsApp Trait: Debugging token", [
                'app_id' => $appId,
                'token_prefix' => substr($token, 0, 8) . '...'
            ]);

            $response = Http::get(self::$facebookAPI . 'debug_token', [
                'input_token' => $token,
                'access_token' => $appToken,
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                Log::error("WhatsApp Trait: Token Debug Failed", [
                    'status' => $response->status(),
                    'error' => $errorData,
                ]);

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured ($appId). Please regenerate your System User Token for this App ID."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'Token debug failed'];
            }

            return [
                'status' => true,
                'data' => $response->json('data')
            ];
        } catch (\Throwable $e) {
            Log::error("WhatsApp Token Debug Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if the app is subscribed to webhooks for this WABA.
     */
    public function checkWebhookSubscription(string $wabaId, string $token): array
    {
        try {
            $url = self::getBaseUrl() . "{$wabaId}/subscribed_apps";
            $appId = config('whatsapp.app_id') ?? config('services.facebook.client_id');
            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');

            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $params = ['app_id' => $appId];
            if (!$isSystemToken) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            $response = Http::withToken($token)->get($url, $params);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                Log::error("WhatsApp Trait: Webhook Check Failed", ['error' => $errorData]);

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured ($appId). Please regenerate your System User Token for this App ID."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'Webhook check failed'];
            }

            $subscriptions = $response->json('data');
            $isSubscribed = collect($subscriptions)->contains('id', $appId);

            return [
                'status' => true,
                'is_subscribed' => $isSubscribed
            ];
        } catch (\Throwable $e) {
            Log::error("WhatsApp Webhook Check Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
