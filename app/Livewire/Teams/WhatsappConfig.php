<?php

namespace App\Livewire\Teams;

use App\Traits\WhatsApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('WhatsApp Configuration')]
class WhatsappConfig extends Component
{
    use \Livewire\WithFileUploads;
    use WhatsApp;

    // Connection Fields
    public $wm_fb_app_id;

    public $wm_fb_app_secret;

    public $wm_business_account_id;

    public $wm_access_token;

    public $outbound_webhook_url;

    // Business Profile Fields
    public $profile_about;

    public $profile_address;

    public $profile_description;

    public $profile_email;

    public $profile_vertical;

    public $profile_websites = [];

    public $profile_picture_url;

    public $profile_photo; // For file upload

    public $is_editing_profile = false;

    // Status
    public $is_webhook_connected = false;

    public $is_whatsmark_connected = false;

    public $webhook_verify_token;

    // Info Fields
    public $wm_default_phone_number;

    public $wm_default_phone_number_id;

    public $available_phone_numbers = [];
    public $available_wabas = [];

    public $wm_messaging_limit;

    public $wm_quality_rating;

    public $wm_phone_display;

    public $wm_verified_name;

    public $wm_business_verification_status = 'unknown'; // verified | not_verified | pending | rejected | unknown

    public $token_info = [];

    public $credits = 0;

    public $credits_total = 1000;

    public $wm_test_message;

    // Health Monitoring
    public $healthScore = 0;

    public $healthStatus = 'unknown';

    public $tokenHealthScore = 0;

    public $token_valid = false;

    public $qualityHealthScore = 0;

    public $messagingUsagePercent = 0;

    public $currentUsage = 0;

    public $dailyLimit = 0;

    public $tokenDaysUntilExpiry = 0;

    public $setupProgress = [];

    public $integrationState = 'disconnected';

    public $integrationStateLabel = 'Disconnected';

    public $integrationStateColor = 'slate';

    public $tokenLastValidated;

    public $tokenExpiresAt;

    public $lastWebhookReceivedAt;

    public $confirmingDisconnect = false;

    public $disconnectConfirmation = '';

    public function sendTestMessage()
    {
        $this->validate([
            'wm_test_message' => 'required', // A phone number or 'ME'
        ]);

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        
        $this->dispatch('notify', title: 'Sending...', message: 'Sending test message to ' . $this->wm_test_message, type: 'info');

        try {
            $waService = new \App\Services\WhatsAppService($team);
            
            // Generate a unique Trace ID for this test
            $traceId = \App\Services\TraceContext::ensureTraceId();
            
            $response = $waService->sendMessage($this->wm_test_message, "Your DigiCloudify WhatsApp connection is active! [Trace: {$traceId}]");

            if ($response['success'] ?? false) {
                $this->dispatch('notify', title: 'Success', message: 'Test message sent successfully. Please check your phone.', type: 'success');
                
                $this->startAudit('test_message', 'completed', [
                    'recipient' => $this->wm_test_message,
                    'message_id' => $response['message_id'] ?? null,
                    'trace_id' => $traceId
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Unknown Meta API error');
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp Test Message Failed', ['error' => $e->getMessage()]);
            $this->dispatch('notify', title: 'Test Failed', message: $e->getMessage(), type: 'error');
            
            $this->startAudit('test_message', 'failed', [
                'recipient' => $this->wm_test_message,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Behavior Settings (Merged from WhatsappSettings)
    public $timezone = 'UTC';

    // Call Settings
    public $callingEnabled = false;

    public $callButtonVisible = false;

    public $callbackPermissionEnabled = false;

    // Advanced Calling Configuration (Phase 5)
    public $stunServers = [];

    public $turnServers = [];

    public $callTimeout = 30; // seconds

    public $maxRetryAttempts = 2;

    public $enableQualityMonitoring = true;

    public $sdpValidationLevel = 'strict'; // strict, moderate, lenient

    public $connectionTimeout = 30; // seconds

    public $iceGatheringTimeout = 10; // seconds

    /**
     * [STAFF-HARDENING] Manually reset the circuit breaker and restore setup state.
     */
    public function resetCircuitBreaker()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        
        $errorKey = "whatsapp_consecutive_errors:{$team->id}";
        \Illuminate\Support\Facades\Cache::forget($errorKey);

        if ($this->integrationState === 'restricted') {
            $team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::READY]);
            $this->loadSettings();
            $this->dispatch('notify', title: 'Circuit Reset', message: 'Connection security lock released.', type: 'success');
            
            $this->startAudit('circuit_reset', 'completed', ['manual_reset' => true]);
        }
    }

    protected $rules = [
        'wm_fb_app_id' => 'nullable',
        'wm_fb_app_secret' => 'nullable',
        'wm_business_account_id' => 'required',
        'wm_access_token' => 'required',
        'wm_default_phone_number_id' => 'nullable',
    ];

    public $readyToLoad = false;

    public function mount()
    {
        $this->loadSettings();

        // Defer heavy API calls to loadData()
    }

    /**
     * [STAFF-HARDENING] Resume an incomplete flow without re-authenticating with Meta.
     */
    public function resumeSetup()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        
        if (!$team->whatsapp_access_token) {
            $this->dispatch('notify', title: 'Error', message: 'No authentication found. Please start over.', type: 'error');
            return;
        }

        $this->dispatch('notify', title: 'Resuming...', message: 'Re-discovering your Meta accounts...', type: 'info');

        try {
            // Re-trigger discovery logic
            $this->loadAvailableWabas();
        } catch (\Exception $e) {
            Log::error('Resume Setup Failed: ' . $e->getMessage());
            $this->dispatch('notify', title: 'Resume Failed', message: 'Could not fetch accounts: ' . $e->getMessage(), type: 'error');
        }
    }

    public function loadAvailableWabas()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        $token = $team->whatsapp_access_token;

        if (!$token) return;

        $wa = new \App\Services\WhatsAppService($team);
        $wabas = $wa->getAvailableWabas($token);

        if ($wabas['status'] && !empty($wabas['data'])) {
            $this->available_wabas = array_map(function($w) {
                return [
                    'id' => $w['id'],
                    'name' => $w['name'] ?? 'WABA ' . $w['id'],
                    'status' => strtoupper($w['account_review_status'] ?? 'unknown'),
                    'verification' => strtoupper($w['business_verification_status'] ?? 'unknown'),
                ];
            }, $wabas['data']);

            $this->dispatch('notify', title: 'Accounts Found', message: count($this->available_wabas) . ' WhatsApp accounts retrieved.', type: 'success');
        } else {
            $this->dispatch('notify', title: 'No Accounts', message: 'No WhatsApp Business Accounts found for this token.', type: 'warning');
        }
    }

    public function loadData()
    {
        $this->readyToLoad = true;

        if ($this->is_whatsmark_connected) {
            $this->loadBusinessProfile();
            $this->refreshHealth();
            $this->loadAvailablePhoneNumbers();

            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
            
            // [PROACTIVE VALIDATION] If last validation is stale (> 6 hours), run a fresh background check
            if ($this->tokenLastValidated && $this->tokenLastValidated->diffInHours(now()) >= 6) {
                \App\Jobs\ValidateWhatsAppTokens::dispatch($team->id)->onQueue('high');
                Log::info("WhatsApp Config: Triggered proactive background validation for Team {$team->id}");
            }

            // Auto-sync once if basic info is missing but we are connected
            if (! $this->wm_verified_name || $this->wm_quality_rating === 'UNKNOWN') {
                $this->syncInfo();
            }

            // [NEW] Self-Heal: Fetch Facebook Business ID if missing
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
            if ($team && $this->is_whatsmark_connected && ! $team->facebook_business_id && $team->whatsapp_business_account_id) {
                // Determine token to use
                $token = $this->wm_access_token ?: $team->whatsapp_access_token;

                if ($token) {
                    $fbId = $this->getFacebookBusinessId($team->whatsapp_business_account_id, $token);
                    if ($fbId) {
                        $team->update(['facebook_business_id' => $fbId]);
                        Log::info("WhatsApp Config: Self-healed Facebook Business ID for Team {$team->id}");
                    }
                }
            }
        }
    }

    public function loadSettings()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();

        if (! $team) {
            $this->is_whatsmark_connected = false;
            $this->integrationState = 'disconnected';

            return;
        }

        // Load from Team Model first, fallback to settings if empty (migration path)
        // Actually, App ID might be global for the SaaS unless white-labeled.
        // Let's stick to global for App ID if it's not in Team.
        // But WABA, Token, PhoneID ARE in Team.

        // [FIXED] Prioritize Team-Specific App ID and Secret (White Label / Manual Connection)
        // Check Team properties followed by settings array
        $this->wm_fb_app_id = $team->whatsapp_app_id 
            ?: ($team->whatsapp_settings['manual_app_id'] ?? get_setting('whatsapp_wm_fb_app_id'));
            
        $this->wm_fb_app_secret = $team->whatsapp_settings['manual_app_secret'] ?? get_setting('whatsapp_wm_fb_app_secret');

        // [FIX Orphaned State]
        // If we have a token but NO WABA ID, the connection is corrupted/partial.
        // We should clear the state to allow fresh connection.
        if (! empty($team->whatsapp_access_token) && empty($team->whatsapp_business_account_id)) {
            // Only clear if it's been more than 5 minutes since update (to allow async process to finish if any)
            $updatedAt = $team->updated_at;
            if ($updatedAt && $updatedAt->diffInMinutes(now()) > 5) {
                Log::warning("Detected Orphaned Token for Team {$team->id}. Clearing to allow reconnection.");
                $team->update(['whatsapp_access_token' => null]);
            }
        }

        if (! $team->whatsapp_connected && $team->hasStoredWhatsAppConnection()) {
            $updates = ['whatsapp_connected' => true];

            if (! $team->whatsapp_setup_state || $team->whatsapp_setup_state === \App\Enums\IntegrationState::DISCONNECTED) {
                $updates['whatsapp_setup_state'] = \App\Enums\IntegrationState::AUTHENTICATED;
            }

            $team->update($updates);
            $team = $team->fresh();

            Log::info("WhatsApp Config: Self-healed stale disconnected flag for Team {$team->id}", [
                'waba_id' => $team->whatsapp_business_account_id,
                'phone_id' => $team->whatsapp_phone_number_id,
            ]);
        }

        if ($team->whatsapp_connected) {
            $this->is_whatsmark_connected = true;
            $this->wm_business_account_id = $team->whatsapp_business_account_id;
        } else {
            $this->is_whatsmark_connected = false;
            $this->wm_business_account_id = null;
        }

        // Always load outbound_webhook_url regardless of connection state —
        // it is stored on the team model and is independent of whatsapp_connected.
        $this->outbound_webhook_url = $team->outbound_webhook_url;

        $this->is_webhook_connected = ! empty($this->outbound_webhook_url);

        // [FIXED] Prioritize Team-Specific Verify Token (Custom App Setup)
        $this->webhook_verify_token = $team->whatsapp_verify_token ?: get_setting('whatsapp_webhook_verify_token');
        
        if (empty($this->webhook_verify_token)) {
            $this->webhook_verify_token = config('whatsapp.webhook_verify_token') ?: Str::random(24);
            set_setting('whatsapp_webhook_verify_token', $this->webhook_verify_token);
        }

        $this->wm_default_phone_number_id = $team->whatsapp_phone_number_id;

        $this->wm_messaging_limit = $team->whatsapp_messaging_limit ?: 'TIER_1K';
        $this->wm_quality_rating = $team->whatsapp_quality_rating ?: 'UNKNOWN';
        $this->wm_phone_display = $team->whatsapp_phone_display ?: '';
        $this->wm_verified_name = $team->whatsapp_verified_name ?: '';
        $this->wm_business_verification_status = $team->whatsapp_business_verification_status ?? 'unknown';
        $this->tokenLastValidated = $team->whatsapp_token_last_validated;
        $this->tokenExpiresAt = $team->whatsapp_token_expires_at;
        $this->lastWebhookReceivedAt = $team->last_webhook_received_at;

        // Derive state from model to avoid mismatch
        $state = $team->whatsapp_setup_state;
        if ($this->is_whatsmark_connected && (! $state || $state === \App\Enums\IntegrationState::DISCONNECTED)) {
            // Self-heal state if data is present
            $state = \App\Enums\IntegrationState::AUTHENTICATED;
            $team->update(['whatsapp_setup_state' => $state]);
        }

        $this->integrationState = $state?->value ?? 'disconnected';
        $this->integrationStateLabel = $state?->label() ?? 'Disconnected';
        $this->integrationStateColor = $state?->color() ?? 'slate';

        // Fetch Real Billing Data
        $wallet = \App\Models\TeamWallet::firstOrCreate(['team_id' => $team->id]);
        $this->credits = $wallet->balance;

        $plan = \App\Models\Plan::where('name', $team->subscription_plan)->first();
        $this->credits_total = $plan ? $plan->message_limit : 1000;

        $this->loadBehaviorSettings($team);
    }

    public function loadBehaviorSettings($team)
    {
        $this->timezone = $team->timezone ?? 'UTC';

        // Load Call Settings
        if (isset($team->whatsapp_settings['calling'])) {
            $this->callingEnabled = $team->whatsapp_settings['calling']['status'] === 'enabled';
            $this->callButtonVisible = $team->whatsapp_settings['calling']['call_icon_visibility'] === 'show';
            $this->callbackPermissionEnabled = ($team->whatsapp_settings['calling']['callback_permission_status'] ?? 'disabled') === 'enabled';
        }

        // Load Advanced Calling Configuration (Phase 5)
        if (isset($team->whatsapp_settings['calling']['advanced'])) {
            $advanced = $team->whatsapp_settings['calling']['advanced'];
            $this->stunServers = $advanced['stun_servers'] ?? [];
            $this->turnServers = $advanced['turn_servers'] ?? [];
            $this->callTimeout = $advanced['call_timeout'] ?? 30;
            $this->maxRetryAttempts = $advanced['max_retry_attempts'] ?? 2;
            $this->enableQualityMonitoring = $advanced['enable_quality_monitoring'] ?? true;
            $this->sdpValidationLevel = $advanced['sdp_validation_level'] ?? 'strict';
            $this->connectionTimeout = $advanced['connection_timeout'] ?? 30;
            $this->iceGatheringTimeout = $advanced['ice_gathering_timeout'] ?? 10;
        } else {
            // Set defaults if not configured
            $this->stunServers = [
                'stun:stun.l.google.com:19302',
                'stun:stun1.l.google.com:19302',
            ];
            $this->turnServers = [];
        }
    }

    public function updateBusinessProfile()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

        if (! \Illuminate\Support\Facades\Auth::user()->ownsTeam($team)) {
            $this->dispatch('notify', title: 'Unauthorized', type: 'error');

            return;
        }

        $this->validate([
            'profile_about' => 'nullable|string|max:130',
            'profile_address' => 'nullable|string|max:256',
            'profile_description' => 'nullable|string|max:512',
            'profile_email' => 'nullable|email|max:128',
            'profile_vertical' => 'nullable|string|max:128',
            'profile_websites.*' => 'nullable|url|max:256',
            'profile_picture_url' => 'nullable|url|max:2048',
            'profile_photo' => 'nullable|image|mimes:jpeg,png|max:5120', // 5MB limit, JPEG/PNG only
        ]);

        try {
            $waService = new \App\Services\WhatsAppService($team);

            // Only send fields that have a value — blank strings cause 400 errors from Meta.
            $profileData = array_filter([
                'about' => $this->profile_about ?: null,
                'address' => $this->profile_address ?: null,
                'description' => $this->profile_description ?: null,
                'email' => $this->profile_email ?: null,
                'vertical' => $this->profile_vertical ?: null,
                'websites' => array_values(array_filter($this->profile_websites)) ?: null,
            ], fn ($v) => $v !== null);

            // Handle Profile Photo Upload
            if ($this->profile_photo) {
                try {
                    $handle = $waService->uploadMediaForTemplate($this->profile_photo);
                    if ($handle) {
                        $profileData['profile_picture_handle'] = $handle;
                    }
                } catch (\Exception $e) {
                    Log::error('Profile Photo Upload Failed: '.$e->getMessage());
                    $this->addError('profile_photo', 'Failed to upload photo: '.$e->getMessage());

                    return; // Stop processing
                }
            }

            $response = $waService->updateBusinessProfile($profileData);

            if ($response['success'] ?? false) {
                $this->dispatch('notify', title: 'Profile Updated', message: 'Business profile updated successfully.', type: 'success');
                $this->is_editing_profile = false;
                $this->profile_photo = null; // Reset
                $this->loadBusinessProfile(); // Reload to reflect changes
            } else {
                $errorMessage = $response['message'] ?? ($response['error']['message'] ?? 'Unknown error from WhatsApp API.');
                Log::error("WhatsApp Profile Update Failed for team {$team->id}: ".json_encode($response));
                $this->dispatch('notify', title: 'Update Failed', message: 'Failed to update business profile: '.$errorMessage, type: 'error');
            }
        } catch (\Exception $e) {
            Log::error("Failed to update WhatsApp business profile for team {$team->id}: ".$e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'An error occurred while updating the business profile: '.$e->getMessage(), type: 'error');
        }
    }

    public function updateBehaviorSettings()
    {
        try {
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

            // Always save timezone locally
            $team->forceFill([
                'timezone' => $this->timezone,
            ])->save();

            // Save calling settings to local cache regardless of Meta response
            $currentSettings = $team->whatsapp_settings ?? [];
            $callingSettings = [
                'status' => $this->callingEnabled ? 'enabled' : 'disabled',
                'call_icon_visibility' => $this->callButtonVisible ? 'show' : 'hide',
                'callback_permission_status' => $this->callbackPermissionEnabled ? 'enabled' : 'disabled',
            ];
            $currentSettings['calling'] = $callingSettings;
            $team->forceFill(['whatsapp_settings' => $currentSettings])->save();

            // Only attempt to sync calling settings with Meta if calling is enabled.
            // When disabled there's nothing meaningful to push and we avoid needless error logs.
            if ($this->callingEnabled) {
                $waService = new \App\Services\WhatsAppService($team);
                $response = $waService->updateSystemCallSettings($callingSettings);

                if ($response['calling_not_supported'] ?? false) {
                    // Meta error #141000 — phone not yet enrolled in Cloud API Calling.
                    // Settings saved locally; show a clear, actionable info notice.
                    $this->dispatch(
                        'notify',
                        title: 'Settings Saved',
                        message: 'Behavior settings saved. Note: WhatsApp Calling is not yet activated on your number — enable it via Meta Business Manager or contact Meta Support.',
                        type: 'info'
                    );

                    return;
                }

                if (! ($response['success'] ?? false)) {
                    // Some other Meta API failure — saved locally but warn about sync.
                    $msg = $response['message'] ?? ($response['error']['message'] ?? 'Unknown Meta API error');
                    $this->dispatch(
                        'notify',
                        title: 'Saved (Meta Sync Failed)',
                        message: 'Settings saved locally. Meta sync failed: '.$msg,
                        type: 'warning'
                    );

                    return;
                }
            }

            // All good — single clean success message
            $this->dispatch(
                'notify',
                title: 'Settings Saved',
                message: 'Behavior settings saved successfully.',
                type: 'success'
            );

        } catch (\Exception $e) {
            Log::error('Failed to update Business Behavior: '.$e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'Failed to save behavior settings: '.$e->getMessage(), type: 'error');
        }
    }

    public function getTimezonesProperty()
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function handleEmbeddedSuccess($accessToken, $wabaId, $wabaOptions = [])
    {
        Log::debug('WhatsApp Setup: Received handleEmbeddedSuccess', [
            'waba_id' => $wabaId,
            'token_prefix' => substr($accessToken, 0, 8).'...',
            'options_count' => count($wabaOptions),
        ]);

        try {
            // Check for WABA Selection Requirement
            if (empty($wabaId) && ! empty($wabaOptions)) {
                $this->available_wabas = $wabaOptions;
                $this->dispatch('notify', title: 'Action Required', message: 'Multiple WhatsApp accounts found. Please select one.', type: 'info');
                return;
            }

            // Funnel into the unified connection sequence
            $this->executeConnectionSequence(
                token: $accessToken,
                wabaId: $wabaId,
                appId: null, // Embedded uses system defaults
                appSecret: null,
                phoneId: null // Auto-discover
            );

        } catch (\Throwable $e) {
            Log::error('WhatsApp Embedded Setup Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch(
                'notify',
                title: 'Link Failed',
                message: 'Could not link WhatsApp account: '.$e->getMessage(),
                type: 'error'
            );
            $this->loadSettings();
        }
    }

    /**
     * Unified Manual Entry Point
     */
    public function connect()
    {
        $this->validate([
            'wm_business_account_id' => 'required',
            'wm_access_token' => $this->wm_access_token ? 'required' : 'nullable',
        ]);

        try {
            $this->executeConnectionSequence(
                token: $this->wm_access_token,
                wabaId: $this->wm_business_account_id,
                appId: $this->wm_fb_app_id,
                appSecret: $this->wm_fb_app_secret,
                phoneId: $this->wm_default_phone_number_id
            );
        } catch (\Throwable $e) {
            // Already handled in executeConnectionSequence but bubbled up
        }
    }

    /**
     * Discovery logic for Manual Flow
     */
    public function discoverManualAccounts()
    {
        $this->validate([
            'wm_access_token' => 'required',
        ]);

        $this->dispatch('notify', title: 'Searching...', message: 'Querying Meta Graph for reachable WABAs...', type: 'info');

        try {
            $wabaIds = $this->getAccessibleWabaIds($this->wm_access_token);
            
            if (empty($wabaIds)) {
                $this->dispatch('notify', title: 'No WABAs Found', message: 'No WhatsApp Business Accounts found for this token. Check permissions.', type: 'warning');
                return;
            }

            $this->available_wabas = [];
            foreach ($wabaIds as $id) {
                $res = $this->mgmt()->getWabaStatus($id, $this->wm_access_token);
                $this->available_wabas[] = [
                    'id' => $id,
                    'name' => $res['data']['name'] ?? 'WABA ' . $id,
                    'status' => strtoupper($res['data']['account_review_status'] ?? 'unknown'),
                    'verification' => strtoupper($res['data']['business_verification_status'] ?? 'unknown'),
                ];
            }

            $this->dispatch('notify', title: 'Accounts Found', message: count($this->available_wabas) . ' accounts discovered. Please select one.', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('notify', title: 'Discovery Failed', message: $e->getMessage(), type: 'error');
        }
    }


    /**
     * THE SINGLE SOURCE OF TRUTH for connecting WhatsApp accounts.
     * All connection flows must funnel through this method.
     */
    protected function executeConnectionSequence(?string $token, string $wabaId, ?string $appId = null, ?string $appSecret = null, ?string $phoneId = null)
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        $auditId = $this->startAudit('connect_unified');

        $tokenToUse = $token ?: $team->whatsapp_access_token;
        if (!$tokenToUse) {
            throw new \Exception('No access token provided.');
        }

        try {
            DB::beginTransaction();

            // 1. Exchange for Long-Lived Token (If not already long-lived)
            $exchangeResult = $this->exchangeForLongLivedToken($tokenToUse);
            if ($exchangeResult['status']) {
                $tokenToUse = $exchangeResult['access_token'];
                Log::info("WhatsApp Setup: Unified flow secured long-lived token for Team {$team->id}");
            }

            // 2. Pre-Verification (Ownership/Access check)
            $debugResult = $this->debugToken($tokenToUse);
            if (!$debugResult['status']) {
                throw new \Exception('Identity Validation Failed: ' . ($debugResult['message'] ?? 'Invalid token'));
            }

            // 3. Duplicate Prevention (Global)
            $duplicate = \App\Models\Team::where('whatsapp_business_account_id', $wabaId)
                ->where('id', '!=', $team->id)
                ->exists();

            if ($duplicate) {
                Log::warning('WhatsApp Setup: Duplicate WABA blocked in unified flow', ['waba_id' => $wabaId]);
                throw new \Exception('This WhatsApp account is already linked to another organization.');
            }

            // 4. Persistence
            $teamSettings = $team->whatsapp_settings ?? [];
            if ($appId && $appId !== get_setting('whatsapp_wm_fb_app_id')) {
                $teamSettings['manual_app_id'] = $appId;
            }
            if ($appSecret && $appSecret !== get_setting('whatsapp_wm_fb_app_secret')) {
                $teamSettings['manual_app_secret'] = $appSecret;
            }

            $team->update([
                'whatsapp_business_account_id' => $wabaId,
                'whatsapp_app_id' => ($appId && $appId !== get_setting('whatsapp_wm_fb_app_id')) ? $appId : null,
                'whatsapp_verify_token' => $this->webhook_verify_token,
                'whatsapp_access_token' => $tokenToUse,
                'whatsapp_phone_number_id' => $phoneId ?: $team->whatsapp_phone_number_id,
                'whatsapp_connected' => true,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::AUTHENTICATED,
                'whatsapp_settings' => $teamSettings,
            ]);

            // Self-heal Facebook Business ID
            $fbBusinessId = $this->getFacebookBusinessId($wabaId, $tokenToUse);
            if ($fbBusinessId) {
                $team->update(['facebook_business_id' => $fbBusinessId]);
            }

            // 5. Automated Webhook Linking
            $subResult = $this->subscribeToWebhooks($wabaId, $tokenToUse);
            if (!(bool)($subResult['status'] ?? $subResult['success'] ?? false)) {
                throw new \Exception('Webhook Mapping Failed: ' . ($subResult['message'] ?? 'Unknown error'));
            }

            // 6. Template & Phone Sync
            $syncRes = $this->loadTemplatesFromWhatsApp($wabaId, $tokenToUse);
            if ($syncRes['status'] && !empty($syncRes['phone_numbers'])) {
                $this->available_phone_numbers = $syncRes['phone_numbers'];
                
                // Auto-pick if only one phone exists and none was selected
                if (!$phoneId && count($this->available_phone_numbers) === 1) {
                    $potentialId = $this->available_phone_numbers[0]['id'];
                    $taken = \App\Models\Team::where('whatsapp_phone_number_id', $potentialId)->where('id', '!=', $team->id)->exists();
                    if (!$taken) {
                        $team->update(['whatsapp_phone_number_id' => $potentialId]);
                    }
                }
            }

            DB::commit();
            $this->completeAudit($auditId, 'completed');

            // 7. Post-Connection Handshake & State Determination
            $this->validateConnection(); // Runs VerificationEngine
            $this->syncInfo();           // Fetches limits, quality, display name
            $this->loadSettings();       // Refreshes UI state
            $this->refreshHealth();     // Calculates health score

            $this->dispatch('notify', title: 'Success', message: 'WhatsApp Account Link Completed Successfully!', type: 'success');

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->completeAudit($auditId, 'failed', ['error' => $e->getMessage()]);

            $team->update([
                'whatsapp_connected' => false,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::DISCONNECTED,
            ]);
            $this->is_whatsmark_connected = false;

            Log::error('WhatsApp Setup: Connection Flow Failed', [
                'message' => $e->getMessage(),
                'team_id' => $team->id,
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('notify', title: 'Connection Failed', message: $e->getMessage(), type: 'error');
        }
    }

    public function validateConnection()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        $engine = app(\App\Services\WhatsAppVerificationEngine::class)->setTeam($team);

        $result = $engine->verify();

        $this->loadSettings(); // Reload to get fresh state details

        if ($result['state']->value !== 'ready') {
            $this->dispatch('notify', 'Warning: Connection verified with issues: '.$result['state']->label());
        } else {
            $this->dispatch('notify', 'Connection verified and ready!');
        }
    }

    public function confirmDisconnect()
    {
        $this->confirmingDisconnect = true;
    }

    public function cancelDisconnect()
    {
        $this->confirmingDisconnect = false;
        $this->disconnectConfirmation = '';
    }

    public function disconnect()
    {
        if ($this->disconnectConfirmation !== 'DISCONNECT') {
            $this->dispatch('notify', title: 'Invalid Confirmation', type: 'error');

            return;
        }

        $this->is_whatsmark_connected = false;
        $this->is_webhook_connected = false;
        // $this->wm_access_token = ''; // Already removed

        if (\Illuminate\Support\Facades\Auth::user()->currentTeam) {
            \Illuminate\Support\Facades\Auth::user()->currentTeam->forceFill([
                'whatsapp_access_token' => null,
                'whatsapp_business_account_id' => null,
                'whatsapp_phone_number_id' => null,
                'whatsapp_connected' => false,
                'whatsapp_token_expires_at' => null,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::DISCONNECTED,
            ])->save();
        }

        $this->confirmingDisconnect = false;
        $this->disconnectConfirmation = '';
        $this->dispatch('notify', 'Disconnected successfully.');
    }

    public function updateOutboundWebhook()
    {
        $this->validate([
            'outbound_webhook_url' => 'nullable|url',
        ]);

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

        if (! $team) {
            $this->dispatch('notify', title: 'Error', message: 'No active team found.', type: 'error');

            return;
        }

        try {
            // Save directly to the Team model — this is where loadSettings() reads from.
            // The set_setting() helper writes to a global settings table (not team-scoped),
            // so it is NOT used for outbound_webhook_url loading. Only the Team column matters.
            $team->forceFill([
                'outbound_webhook_url' => $this->outbound_webhook_url ?: null,
            ])->save();

            // Update the local Livewire state to reflect the saved value
            $this->outbound_webhook_url = $team->fresh()->outbound_webhook_url;
            $this->is_webhook_connected = ! empty($this->outbound_webhook_url);

            $this->dispatch('notify', title: 'Webhook Saved', message: 'Outbound webhook URL updated successfully.', type: 'success');
        } catch (\Exception $e) {
            Log::error("Failed to update outbound webhook for team {$team->id}: ".$e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'Failed to save webhook URL: '.$e->getMessage(), type: 'error');
        }
    }

    public function setupWebhook()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        if (! $team->whatsapp_access_token || ! $this->wm_business_account_id) {
            $this->dispatch('notify', 'Missing configuration. Please connect first.');

            return;
        }

        $result = $this->subscribeToWebhooks($this->wm_business_account_id, $team->whatsapp_access_token);

        if ((bool) ($result['status'] ?? $result['success'] ?? false)) {
            $this->dispatch('notify', 'Webhook subscribed successfully!');
            $this->refreshHealth();
        } else {
            $this->dispatch('notify', 'Webhook subscription failed: '.($result['message'] ?? $result['error'] ?? 'Unknown error'));
        }
    }

    public function syncInfo()
    {
        if (! $this->wm_default_phone_number_id) {
            $this->dispatch('notify', 'No Phone Number ID configured. Please connect first.');

            return;
        }

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();

        if (! $team->whatsapp_business_account_id || ! $team->whatsapp_access_token) {
            $this->dispatch('notify', 'WhatsApp configuration missing. Please reconnect.');

            return;
        }

        // Ensure webhook is subscribed during sync (only if WABA ID is available)
        if ($team->whatsapp_business_account_id && $team->whatsapp_access_token) {
            $this->subscribeToWebhooks($team->whatsapp_business_account_id, $team->whatsapp_access_token);
        }

        $result = $this->getPhoneNumberDetails($this->wm_default_phone_number_id);

        if ($result['status']) {
            $data = $result['data'];

            // Update local state
            $this->wm_messaging_limit = $data['messaging_limit_tier'];
            $this->wm_quality_rating = $data['quality_rating'];
            $this->wm_phone_display = $data['display_phone_number'];
            $this->wm_verified_name = $data['verified_name'] ?? '';

            // Persist to Team
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
            $team->update([
                'whatsapp_messaging_limit' => $this->wm_messaging_limit,
                'whatsapp_quality_rating' => $this->wm_quality_rating,
                'whatsapp_phone_display' => $this->wm_phone_display,
                'whatsapp_verified_name' => $this->wm_verified_name,
            ]);

            // Also reload business profile details
            $this->loadBusinessProfile();

            // Check business verification status from Meta
            $this->checkBusinessVerification();

            $this->dispatch('notify', 'Account info synced successfully!');
        } else {
            $this->dispatch('notify', 'Sync failed: '.$result['message']);
        }
        $this->refreshHealth();
    }

    /**
     * Fetch and persist the Business Verification Status from Meta.
     */
    public function checkBusinessVerification()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();

        if (! $team->whatsapp_business_account_id || ! $team->whatsapp_access_token) {
            return;
        }

        try {
            $result = $this->getBusinessVerificationStatus(
                $team->whatsapp_business_account_id,
                $team->whatsapp_access_token
            );

            if ($result['status']) {
                $this->wm_business_verification_status = $result['verification_status'];

                // Persist to DB so it survives page reloads
                $team->update([
                    'whatsapp_business_verification_status' => $this->wm_business_verification_status,
                ]);

                Log::info('WhatsApp Config: Business verification status updated', [
                    'team_id' => $team->id,
                    'status' => $this->wm_business_verification_status,
                ]);
            } else {
                Log::warning('WhatsApp Config: Could not fetch business verification status', [
                    'message' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp Config: Exception while checking business verification: '.$e->getMessage());
        }
    }

    public function refreshHealth()
    {
        if (! $this->is_whatsmark_connected) {
            return;
        }

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();
        $monitor = app(\App\Services\WhatsAppHealthMonitor::class);
        $health = $monitor->checkHealth($team);

        // After checkHealth, the team model has been refreshed with data from Meta
        $this->wm_messaging_limit = $team->whatsapp_messaging_limit ?: 'TIER_1K';
        $this->wm_quality_rating = $team->whatsapp_quality_rating ?: 'UNKNOWN';
        $this->wm_phone_display = $team->whatsapp_phone_display ?: '';
        $this->wm_verified_name = $team->whatsapp_verified_name ?: '';
        $this->wm_business_verification_status = $team->whatsapp_business_verification_status ?? 'unknown';

        $this->healthScore = $health['overall_score'] ?? 0;
        $this->healthStatus = $health['status'] ?? 'unknown';
        $this->tokenHealthScore = $health['token']['score'] ?? 0;
        $this->token_valid = $health['token']['valid'] ?? true;
        $this->qualityHealthScore = $health['quality']['score'] ?? 0;
        $this->messagingUsagePercent = $health['messaging']['usage_percent'] ?? 0;
        // [FIX] Handle permanent tokens (null expiry) by defaulting to 999 instead of 0
        $this->tokenDaysUntilExpiry = $health['token']['days_remaining'] ?? 999;
        $this->currentUsage = $health['messaging']['current_usage'] ?? 0;
        $this->dailyLimit = $health['messaging']['daily_limit'] ?? 0;

        $this->setupProgress = $this->getSetupProgress();

        $this->integrationState = $team->whatsapp_setup_state?->value ?? 'disconnected';
        $this->integrationStateLabel = $team->whatsapp_setup_state?->label() ?? 'Disconnected';
        $this->integrationStateColor = $team->whatsapp_setup_state?->color() ?? 'slate';
    }

    public function getSetupProgress()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();
        $state = $team->whatsapp_setup_state;

        $steps = [
            [
                'id' => 'connect_account',
                'title' => 'Connect Account',
                'status' => $team->whatsapp_access_token
                    ? ($state && in_array($state->value, ['suspended', 'disconnected']) ? 'warning' : 'completed')
                    : 'not_started',
                'description' => $state && $state->value === 'suspended'
                    ? 'Connection Suspended (Unauthorized)'
                    : ($team->whatsapp_access_token ? 'Connected' : 'Not connected'),
                'icon' => 'key',
            ],
            [
                'id' => 'select_phone',
                'title' => 'Select Phone Number',
                'status' => $team->whatsapp_phone_number_id
                    ? (in_array($state?->value, ['authenticated']) ? 'pending' : 'completed')
                    : 'not_started',
                'description' => $this->wm_phone_display ?: 'No phone selected',
                'icon' => 'phone',
            ],
            [
                'id' => 'configure_profile',
                'title' => 'Configure Business Profile',
                'status' => $this->profile_description ? 'completed' : 'not_started',
                'description' => $this->profile_description ? 'Profile description set' : 'Profile incomplete',
                'icon' => 'user-circle',
            ],
            [
                'id' => 'webhook_setup',
                'title' => 'Webhook Setup',
                'status' => $state && ($state->isReady() || in_array($state, [IntegrationState::READY_WARNING, IntegrationState::RESTRICTED])) ? 'completed' : 'not_started',
                'description' => $state && ($state->isReady() || in_array($state, [IntegrationState::READY_WARNING, IntegrationState::RESTRICTED])) ? 'Receiving events' : 'Not configured',
                'icon' => 'webhook',
            ],
            [
                'id' => 'sync_templates',
                'title' => 'Sync Message Templates',
                'status' => $team->whatsappTemplates()->count() > 0 ? 'completed' : 'not_started',
                'description' => $team->whatsappTemplates()->count().' templates synced',
                'icon' => 'template',
            ],
            [
                'id' => 'system_ready',
                'title' => 'Messaging Ready',
                'status' => $state && ($state->isReady() || $state === IntegrationState::READY_WARNING) ? 'completed' : ($state === IntegrationState::RESTRICTED ? 'warning' : 'not_started'),
                'description' => $state ? $state->label() : 'Pending verification',
                'icon' => 'check-circle',
            ],
        ];

        $completed = collect($steps)->where('status', 'completed')->count();
        $total = count($steps);
        $progress = round(($completed / $total) * 100);

        return [
            'steps' => $steps,
            'completed' => $completed,
            'total' => $total,
            'progress' => $progress,
        ];
    }

    public $registrationPin = '';

    public function registerNumber()
    {
        if (! $this->wm_default_phone_number_id) {
            $this->dispatch('notify', 'No Phone Number ID found.');

            return;
        }

        $this->validate([
            'registrationPin' => 'required|digits:6',
        ]);

        $result = $this->registerPhone($this->wm_default_phone_number_id, $this->registrationPin);

        if ($result['status']) {
            $this->dispatch('notify', 'Phone number registered successfully.');
            // Re-sync info after registration just in case
            $this->syncInfo();
        } else {
            $this->dispatch('notify', 'Registration failed: '.$result['message']);
        }
    }

    public function loadBusinessProfile()
    {
        try {
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();
            if (! $team->whatsapp_access_token || ! $team->whatsapp_phone_number_id) {
                return;
            }

            $service = app(\App\Services\WhatsAppService::class);
            $service->setTeam($team);
            $response = $service->getBusinessProfile();

            if (isset($response['data']['data'][0])) {
                $profile = $response['data']['data'][0];
                $this->profile_about = $profile['about'] ?? '';
                $this->profile_address = $profile['address'] ?? '';
                $this->profile_description = $profile['description'] ?? '';
                $this->profile_email = $profile['email'] ?? '';
                $this->profile_vertical = $profile['vertical'] ?? '';
                $this->profile_websites = $profile['websites'] ?? [];
                $this->profile_picture_url = $profile['profile_picture_url'] ?? '';

                $this->dispatch('notify', 'Business profile data fetched from WhatsApp!');
            } elseif (isset($response['error'])) {
                \Illuminate\Support\Facades\Log::error('WhatsApp Profile API Error: '.json_encode($response['error']));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load WhatsApp Business Profile: '.$e->getMessage());
        }
    }

    public function addWebsite()
    {
        if (count($this->profile_websites) < 2) {
            $this->profile_websites[] = '';
        } else {
            $this->dispatch('notify', title: 'Limit Reached', message: 'You can add up to 2 websites.', type: 'warning');
        }
    }

    public function removeWebsite($index)
    {
        unset($this->profile_websites[$index]);
        $this->profile_websites = array_values($this->profile_websites);
    }

    public function editProfile()
    {
        $this->is_editing_profile = true;
    }

    public function loadAvailablePhoneNumbers()
    {
        if (! $this->wm_business_account_id) {
            return;
        }

        try {
            // Re-use loadTemplates as it fetches phones.
            // Ideally we'd have a separate method, but for now this works and refreshes templates too.
            // Or just fetch phone numbers directly if templates are heavy.
            // Let's implement a direct fetch for speed if needed, but loadTemplates is already there.
            // To avoid template parsing overhead, we could do a direct API call here.

            // Optimization: Just use template sync as it's cached/updated rarely?
            // Let's use getPhoneNumberDetails but that needs an ID.
            // We need LIST of phones.
            // Let's use the trait's logic but customized?
            // Actually, calling loadTemplates every mount is heavy.
            // Let's only do it if wm_default_phone_number_id is empty OR requested.

            // For now, let's just make a simple call if we don't have numbers yet.
            if (empty($this->available_phone_numbers) && $this->is_whatsmark_connected) {
                $token = $this->wm_access_token ?: \Illuminate\Support\Facades\Auth::user()->currentTeam->whatsapp_access_token;
                if (! $token) {
                    return;
                }

                $wabaId = $this->wm_business_account_id;
                $url = 'https://graph.facebook.com/'.$this->getApiVersion()."/{$wabaId}/phone_numbers";
                $response = \Illuminate\Support\Facades\Http::withToken($token)->get($url);

                if ($response->successful()) {
                    $this->available_phone_numbers = $response->json('data') ?? [];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load available phone numbers: '.$e->getMessage());
        }
    }

    public function selectWaba($wabaId)
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

        // Uniqueness check for non-enterprise
        $existing = \App\Models\Team::where('whatsapp_business_account_id', $wabaId)
            ->where('id', '!=', $team->id)
            ->whereIn('subscription_status', ['trial', 'expired'])
            ->first();

        if ($existing) {
            $this->dispatch('notify', title: 'Account Taken', message: 'This WhatsApp account is already linked to another trial team.', type: 'error');

            return;
        }

        $this->wm_business_account_id = $wabaId;
        $team->update(['whatsapp_business_account_id' => $wabaId]);

        $this->available_wabas = []; // Clear list after selection
        $this->connect(); // Proceed with connection

        $this->dispatch('notify', 'WhatsApp Business Account selected successfully.');
    }

    public function selectPhoneNumber($phoneId, $displayPhone)
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

        // Uniqueness check
        $existing = \App\Models\Team::where('whatsapp_phone_number_id', $phoneId)
            ->where('id', '!=', $team->id)
            ->first();

        if ($existing) {
            // Warn or Ask? For now, we'll force claim since user is explicitly selecting it.
            // But we should probably detach it from the other team to avoid webhook conflicts?
            // Yes, detach from other team.
            $existing->update([
                'whatsapp_phone_number_id' => null,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::DISCONNECTED, // Partial disconnect
            ]);
            Log::warning("Phone ID $phoneId reclaimed from Team {$existing->id} by Team {$team->id}");
        }

        $this->wm_default_phone_number_id = $phoneId;
        $this->wm_phone_display = $displayPhone;

        $team->update([
            'whatsapp_phone_number_id' => $phoneId,
        ]);

        $this->dispatch('notify', 'Phone Number selected successfully.');
        $this->syncInfo(); // Get details immediately
    }

    public function cancelEdit()
    {
        $this->is_editing_profile = false;
        $this->loadBusinessProfile(); // Revert changes by reloading
    }

    /**
     * Start audit trail for setup operation
     */
    private function startAudit(string $action): int
    {
        return \App\Models\WhatsAppSetupAudit::create([
            'team_id' => \Illuminate\Support\Facades\Auth::user()->currentTeam->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'action' => $action,
            'status' => 'in_progress',
            'ip_address' => request()->ip(),
            'reference_id' => \App\Models\WhatsAppSetupAudit::generateReferenceId(),
        ])->id;
    }

    /**
     * Complete audit trail
     */
    private function completeAudit(int $auditId, string $status, array $metadata = []): void
    {
        \App\Models\WhatsAppSetupAudit::find($auditId)?->update([
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Classify error type for better user guidance
     */
    private function classifyError(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, 'token') || str_contains($message, 'auth') || str_contains($message, '190')) {
            return 'auth';
        }

        if (str_contains($message, 'network') || str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            return 'network';
        }

        if (str_contains($message, 'rate') || str_contains($message, '429') || str_contains($message, 'limit')) {
            return 'rate_limit';
        }

        return 'unknown';
    }

    public function render()
    {
        return view('livewire.teams.whatsapp-config');
    }
}
