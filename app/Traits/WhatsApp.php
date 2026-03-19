<?php

namespace App\Traits;

use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WhatsApp
{
    public const ERROR_TOKEN_INVALID = 190;
    public const ERROR_PASSWORD_CHANGED = 460;

    protected static string $facebookAPI = 'https://graph.facebook.com/';

    protected ?\App\Core\WhatsApp\ManagementClient $mgmtClient = null;
    protected bool $skipAppSecretProof = false;

    protected function mgmt(): \App\Core\WhatsApp\ManagementClient
    {
        if (!$this->mgmtClient) {
            $this->mgmtClient = app(\App\Core\WhatsApp\ManagementClient::class);
            if (isset($this->team)) {
                $this->mgmtClient->forTeam($this->team);
            }
        }
        return $this->mgmtClient;
    }

    private function getToken(): string
    {
        return $this->team->whatsapp_access_token ?? auth()->user()->currentTeam->whatsapp_access_token ?? '';
    }

    private function getAccountID(): string
    {
        return $this->team->whatsapp_business_account_id ?? auth()->user()->currentTeam->whatsapp_business_account_id ?? '';
    }

    protected function getApiVersion(): string
    {
        return config('whatsapp.api_version', 'v21.0');
    }

    protected static function getBaseUrl(): string
    {
        $version = config('whatsapp.api_version', 'v21.0');
        return "https://graph.facebook.com/{$version}/";
    }

    protected function setSkipAppSecretProof(bool $skip): void
    {
        $this->skipAppSecretProof = $skip;
    }

    /**
     * Handle cases where the access token is invalid, expired, or revoked.
     */
    protected function handleTokenFailure($response, string $context = 'API Call', ?array $errorData = null): void
    {
        $status = $response?->status() ?? 401;
        $errorData = $errorData ?? $response?->json();
        $errorCode = $errorData['error']['code'] ?? $errorData['code'] ?? null;
        $subcode = $errorData['error']['error_subcode'] ?? $errorData['error_subcode'] ?? null;

        // Code 190: Access token has expired or is invalid
        // Status 401: Unauthorized (standard for token issues)
        if ($status === 401 || $errorCode == self::ERROR_TOKEN_INVALID) {
            $team = $this->team ?? auth()->user()?->currentTeam;
            if ($team) {
                // Determine if it was a password change (#460) or general expiry
                $reason = "Session invalidated (Code: {$errorCode}, Sub: {$subcode})";
                if ($subcode == self::ERROR_PASSWORD_CHANGED) {
                    $reason = "Session invalidated: Password changed on Facebook account.";
                    $this->triggerHealthNotification($team, 'token_password_changed', "Your WhatsApp connection has been disconnected because of a password change on the linked Facebook account. Please reconnect to restore service.");
                }

                $team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::SUSPENDED]);

                Log::warning("WhatsApp Trait: [{$context}] Detected invalid token for team {$team->id}. Reason: {$reason}");
            }
        }
    }

    protected function triggerHealthNotification($team, string $type, string $message): void
    {
        try {
            if ($team && $team->owner) {
                $team->owner->notify(new \App\Notifications\WhatsAppHealthNotification($team, $type, $message));
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp Trait: Failed to send health notification: " . $e->getMessage());
        }
    }

    public function loadTemplatesFromWhatsApp(?string $wabaId = null, ?string $token = null): array
    {
        $team = $this->team ?? auth()->user()->currentTeam;
        if (!$team) {
            return ['status' => false, 'message' => 'No contextual team found.'];
        }
        
        $res = app(\App\Services\WhatsApp\TemplateService::class)
            ->setTeam($team)
            ->syncTemplates();

        return [
            'status' => $res['success'],
            'message' => $res['success'] ? 'Templates synced' : ($res['error'] ?? 'Unknown error'),
            'count' => $res['count'] ?? 0
        ];
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

            if (!$isSystemToken && $appSecret && !$this->skipAppSecretProof) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            $response = Http::timeout(15)->get(self::getBaseUrl() . "{$phoneNumberId}", $params);

            // Retry without appsecret_proof if it fails with specific error
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {

                    Log::warning("WhatsApp Trait: AppSecret Proof failed for Phone Details, retrying without proof.");
                    $this->skipAppSecretProof = true;
                    unset($params['appsecret_proof']);
                    $response = Http::timeout(15)->get(self::getBaseUrl() . "{$phoneNumberId}", $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';
                $this->handleTokenFailure($response, 'Phone Details');

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

            if (!$isSystemToken && $appSecret && !$this->skipAppSecretProof) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            Log::debug("WhatsApp Trait: Registering phone", [
                'phone_id' => $phoneNumberId,
                'is_system_token' => $isSystemToken
            ]);

            $response = Http::timeout(15)->withToken($token)->post($url, $params);

            // Retry without appsecret_proof if it fails with specific error
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {

                    Log::warning("WhatsApp Trait: AppSecret Proof failed for Registration, retrying without proof.");
                    $this->skipAppSecretProof = true;
                    unset($params['appsecret_proof']);
                    $response = Http::timeout(15)->withToken($token)->post($url, $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';
                $this->handleTokenFailure($response, 'Phone Registration');

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
        return $this->mgmt()->exchangeToken($shortLivedToken);
    }

    /**
     * Subscribe the application to the WhatsApp Business Account's webhooks.
     */
    public function subscribeToWebhooks(string $wabaId, string $token): array
    {
        return $this->mgmt()->subscribeToWebhooks($wabaId, $token);
    }

    public function debugToken(string $token): array
    {
        try {
            // Priority: Settings DB (UI-entered) > WHATSAPP_APP_ID env > FACEBOOK_APP_ID env
            // The settings DB key matches what WhatsappConfig::loadSettings() reads (whatsapp_wm_fb_app_id).
            $appId = get_setting('whatsapp_wm_fb_app_id')
                ?: config('whatsapp.app_id');
            $appSecret = config('whatsapp.app_secret');
            $appToken = $appId . '|' . $appSecret;

            if (empty($appId) || empty($appSecret)) {
                Log::warning("WhatsApp Trait: debugToken skipped — App ID or Secret not configured.");
                return ['status' => false, 'message' => 'WhatsApp App ID or Secret not configured.'];
            }

            Log::debug("WhatsApp Trait: Debugging token", [
                'app_id' => $appId,
                'token_prefix' => substr($token, 0, 8) . '...'
            ]);

            $response = Http::timeout(15)->get(self::$facebookAPI . 'debug_token', [
                'input_token' => $token,
                'access_token' => $appToken,
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    Log::warning("WhatsApp Trait: Token Debug Mismatch — token was generated under a different App ID.", [
                        'config_app_id' => $appId,
                        'hint' => 'Set WHATSAPP_APP_ID in .env to the App ID your System User token belongs to, or update it via the WhatsApp Setup page.',
                    ]);
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured ({$appId}). Set WHATSAPP_APP_ID in your .env to the correct App ID."];
                }

                $this->handleTokenFailure($response, 'Token Debug');

                Log::error("WhatsApp Trait: Token Debug Failed", [
                    'status' => $response->status(),
                    'error' => $errorData,
                ]);

                return ['status' => false, 'message' => $errorMessage ?? 'Token debug failed'];
            }

            $data = $response->json('data');
            $isValid = $data['is_valid'] ?? false;

            if (!$isValid) {
                // If it's invalid, trigger the failure handler to suspend/notify
                $this->handleTokenFailure($response, 'Token Debug', $data['error'] ?? $data);

                $errorMsg = $data['error']['message'] ?? 'Token is invalid or has expired.';
                return [
                    'status' => false,
                    'message' => $errorMsg,
                    'data' => $data
                ];
            }

            return [
                'status' => true,
                'data' => $data
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

            $params = [];
            if ($appId) {
                $params['app_id'] = $appId;
            }
            if (!$isSystemToken && !$this->skipAppSecretProof) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            $response = Http::timeout(15)->withToken($token)->get($url, $params);

            // Retry without appsecret_proof if it fails with specific error
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {

                    Log::warning("WhatsApp Trait: AppSecret Proof failed on Check, retrying without proof.");
                    $this->skipAppSecretProof = true;
                    unset($params['appsecret_proof']);
                    $response = Http::timeout(15)->withToken($token)->get($url, $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                $errorCode = $errorData['error']['code'] ?? null;
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';
                $this->handleTokenFailure($response, 'Webhook Check');

                Log::error("WhatsApp Trait: Webhook Check Failed", ['error' => $errorData]);

                if ($errorCode == 100 && str_contains($errorMessage, 'App_id in the input_token did not match')) {
                    return ['status' => false, 'message' => "Configuration Error: The Access Token belongs to a different App ID than the one currently configured ($appId). Please regenerate your System User Token for this App ID."];
                }

                return ['status' => false, 'message' => $errorMessage ?? 'Webhook check failed'];
            }

            $subscriptions = $response->json('data');

            Log::debug("WhatsApp Trait: Checking Webhook Subscription", [
                'configured_app_id' => $appId,
                'found_subscriptions' => $subscriptions
            ]);

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
    /**
     * Fetch WABA IDs accessible by the user token.
     */
    public function getAccessibleWabaIds(string $token): array
    {
        try {
            $wabaIds = [];

            // Direct user edges (covers different app setups and account ownership modes).
            $wabaIds = array_merge($wabaIds, $this->fetchPaginatedGraphIds($token, 'me/whatsapp_business_accounts'));
            $wabaIds = array_merge($wabaIds, $this->fetchPaginatedGraphIds($token, 'me/client_whatsapp_business_accounts'));

            // Business-level edges (embedded signup often grants access through BM ownership/client links).
            $businessIds = $this->fetchPaginatedGraphIds($token, 'me/businesses');
            foreach ($businessIds as $businessId) {
                $wabaIds = array_merge($wabaIds, $this->fetchPaginatedGraphIds($token, $businessId . '/owned_whatsapp_business_accounts'));
                $wabaIds = array_merge($wabaIds, $this->fetchPaginatedGraphIds($token, $businessId . '/client_whatsapp_business_accounts'));
            }

            $wabaIds = array_values(array_unique(array_filter($wabaIds)));

            Log::debug('WhatsApp Trait: Accessible WABA discovery result', [
                'count' => count($wabaIds),
                'waba_ids' => $wabaIds,
            ]);

            return $wabaIds;
        } catch (\Throwable $e) {
            Log::error("WhatsApp Trait: Failed to fetch accessible WABA IDs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch all IDs from a Graph edge, following pagination.
     */
    private function fetchPaginatedGraphIds(string $token, string $edgePath): array
    {
        $ids = [];
        $nextUrl = self::getBaseUrl() . ltrim($edgePath, '/');

        while ($nextUrl) {
            $response = Http::timeout(15)->withToken($token)->get($nextUrl);

            if ($response->failed()) {
                Log::warning('WhatsApp Trait: Graph edge fetch failed', [
                    'edge' => $edgePath,
                    'status' => $response->status(),
                    'error' => $response->json('error.message') ?? 'Unknown error',
                ]);
                break;
            }

            foreach (($response->json('data') ?? []) as $row) {
                if (!empty($row['id'])) {
                    $ids[] = (string) $row['id'];
                }
            }

            $nextUrl = $response->json('paging.next');
        }

        return $ids;
    }

    /**
     * Get the Business Verification Status from Meta's WABA API.
     * Returns 'verified', 'not_verified', 'pending', 'rejected' or null on error.
     */
    public function getBusinessVerificationStatus(?string $wabaId = null, ?string $token = null): array
    {
        try {
            $accountId = $wabaId ?? $this->getAccountID();
            $token = $token ?? $this->getToken();

            if (empty($accountId) || empty($token)) {
                return ['status' => false, 'message' => 'WABA ID or Access Token not configured.'];
            }

            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');
            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $params = [
                'fields' => 'id,name,account_review_status,business_verification_status',
                'access_token' => $token,
            ];

            if (!$isSystemToken && $appSecret && !$this->skipAppSecretProof) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            Log::debug('WhatsApp Trait: Fetching business verification status', [
                'waba_id' => $accountId,
                'is_system_token' => $isSystemToken,
            ]);

            $response = Http::timeout(15)->get(self::getBaseUrl() . "{$accountId}", $params);

            // Retry without appsecret_proof on proof errors
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {
                    Log::warning('WhatsApp Trait: AppSecret Proof failed for verification status, retrying without proof.');
                    $this->skipAppSecretProof = true;
                    unset($params['appsecret_proof']);
                    $response = Http::timeout(15)->get(self::getBaseUrl() . "{$accountId}", $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown Error';

                $this->handleTokenFailure($response, 'Business Verification Status');
                Log::error('WhatsApp Trait: Failed to fetch business verification status', [
                    'status' => $response->status(),
                    'error' => $errorData,
                ]);
                return ['status' => false, 'message' => $errorMessage];
            }

            $data = $response->json();

            // Prefer `business_verification_status` (BM-level), fall back to `account_review_status`
            $verificationStatus = $data['business_verification_status']
                ?? $data['account_review_status']
                ?? null;

            Log::info('WhatsApp Trait: Business verification status fetched', [
                'waba_id' => $accountId,
                'verification_status' => $verificationStatus,
                'raw_data' => $data,
            ]);

            return [
                'status' => true,
                'verification_status' => strtolower($verificationStatus ?? 'unknown'),
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp Trait: Error fetching business verification status: ' . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch the Facebook Business Manager ID from a WhatsApp Business Account.
     */
    public function getFacebookBusinessId(string $wabaId, string $token): ?string
    {
        try {
            $appSecret = config('whatsapp.app_secret') ?? config('services.facebook.client_secret');
            $isSystemToken = str_starts_with($token, 'EAAB');
            $appSecretProof = hash_hmac('sha256', $token, $appSecret);

            $params = [
                'fields' => 'owner_business_info',
                'access_token' => $token,
            ];

            if (!$isSystemToken && $appSecret && !$this->skipAppSecretProof) {
                $params['appsecret_proof'] = $appSecretProof;
            }

            Log::debug("WhatsApp Trait: Fetching Facebook Business ID", [
                'waba_id' => $wabaId,
                'is_system_token' => $isSystemToken
            ]);

            $response = Http::get(self::getBaseUrl() . "{$wabaId}", $params);

            // Retry without appsecret_proof if it fails
            if ($response->failed()) {
                $errorData = $response->json();
                if (
                    ($errorData['error']['code'] ?? 0) == 100 &&
                    str_contains($errorData['error']['message'] ?? '', 'Invalid appsecret_proof')
                ) {
                    Log::warning("WhatsApp Trait: AppSecret Proof failed for Business ID fetch, retrying without proof.");
                    $this->skipAppSecretProof = true;
                    unset($params['appsecret_proof']);
                    $response = Http::get(self::getBaseUrl() . "{$wabaId}", $params);
                }
            }

            if ($response->failed()) {
                $errorData = $response->json();
                Log::error("WhatsApp Trait: Failed to fetch Facebook Business ID", [
                    'status' => $response->status(),
                    'error' => $errorData
                ]);
                return null;
            }

            $data = $response->json();
            $businessId = $data['owner_business_info']['id'] ?? null;

            if ($businessId) {
                Log::info("WhatsApp Trait: Successfully fetched Facebook Business ID", [
                    'waba_id' => $wabaId,
                    'business_id' => $businessId
                ]);
            }

            return $businessId;
        } catch (\Throwable $e) {
            Log::error("WhatsApp Trait: Error fetching Facebook Business ID: " . $e->getMessage());
            return null;
        }
    }
}
