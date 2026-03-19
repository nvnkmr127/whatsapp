<?php

namespace App\Livewire\Teams;

use App\Traits\WhatsApp;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;

#[Title('WhatsApp Configuration')]
class WhatsappConfig extends Component
{
    use WhatsApp;
    use \Livewire\WithFileUploads;

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

    public $confirmingDisconnect = false;
    public $disconnectConfirmation = '';

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

    public function loadData()
    {
        $this->readyToLoad = true;

        if ($this->is_whatsmark_connected) {
            $this->loadBusinessProfile();
            $this->refreshHealth();
            $this->loadAvailablePhoneNumbers();

            // Auto-sync once if basic info is missing but we are connected
            if (!$this->wm_verified_name || $this->wm_quality_rating === 'UNKNOWN') {
                $this->syncInfo();
            }

            // [NEW] Self-Heal: Fetch Facebook Business ID if missing
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
            if ($team && $this->is_whatsmark_connected && !$team->facebook_business_id && $team->whatsapp_business_account_id) {
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

        if (!$team) {
            $this->is_whatsmark_connected = false;
            $this->integrationState = 'disconnected';
            return;
        }

        // Load from Team Model first, fallback to settings if empty (migration path)
        // Actually, App ID might be global for the SaaS unless white-labeled. 
        // Let's stick to global for App ID if it's not in Team. 
        // But WABA, Token, PhoneID ARE in Team.

        // [FIXED] Prioritize Team-Specific App ID and Verify Token (White Label / Manual Connection)
        $this->wm_fb_app_id = $team->whatsapp_app_id ?: get_setting('whatsapp_wm_fb_app_id');
        $this->wm_fb_app_secret = get_setting('whatsapp_wm_fb_app_secret');

        // [FIX Orphaned State]
        // If we have a token but NO WABA ID, the connection is corrupted/partial.
        // We should clear the state to allow fresh connection.
        if (!empty($team->whatsapp_access_token) && empty($team->whatsapp_business_account_id)) {
            // Only clear if it's been more than 5 minutes since update (to allow async process to finish if any)
            $updatedAt = $team->updated_at;
            if ($updatedAt && $updatedAt->diffInMinutes(now()) > 5) {
                Log::warning("Detected Orphaned Token for Team {$team->id}. Clearing to allow reconnection.");
                $team->update(['whatsapp_access_token' => null]);
            }
        }

        if (!$team->whatsapp_connected && $team->hasStoredWhatsAppConnection()) {
            $updates = ['whatsapp_connected' => true];

            if (!$team->whatsapp_setup_state || $team->whatsapp_setup_state === \App\Enums\IntegrationState::DISCONNECTED) {
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

        $this->is_webhook_connected = !empty($this->outbound_webhook_url);

        $this->webhook_verify_token = $team->whatsapp_verify_token ?: get_setting('whatsapp_webhook_verify_token');
        if (empty($this->webhook_verify_token)) {
            $this->webhook_verify_token = Str::random(16);
            if (!$team->whatsapp_verify_token) {
                 set_setting('whatsapp_webhook_verify_token', $this->webhook_verify_token);
            }
        }

        $this->wm_default_phone_number_id = $team->whatsapp_phone_number_id;

        $this->wm_messaging_limit = $team->whatsapp_messaging_limit ?: 'TIER_1K';
        $this->wm_quality_rating = $team->whatsapp_quality_rating ?: 'UNKNOWN';
        $this->wm_phone_display = $team->whatsapp_phone_display ?: '';
        $this->wm_verified_name = $team->whatsapp_verified_name ?: '';
        $this->wm_business_verification_status = $team->whatsapp_business_verification_status ?? 'unknown';
        $this->tokenLastValidated = $team->whatsapp_token_last_validated;
        $this->tokenExpiresAt = $team->whatsapp_token_expires_at;

        // Derive state from model to avoid mismatch
        $state = $team->whatsapp_setup_state;
        if ($this->is_whatsmark_connected && (!$state || $state === \App\Enums\IntegrationState::DISCONNECTED)) {
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

        if (!\Illuminate\Support\Facades\Auth::user()->ownsTeam($team)) {
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
            ], fn($v) => $v !== null);

            // Handle Profile Photo Upload
            if ($this->profile_photo) {
                try {
                    $handle = $waService->uploadMediaForTemplate($this->profile_photo);
                    if ($handle) {
                        $profileData['profile_picture_handle'] = $handle;
                    }
                } catch (\Exception $e) {
                    Log::error("Profile Photo Upload Failed: " . $e->getMessage());
                    $this->addError('profile_photo', 'Failed to upload photo: ' . $e->getMessage());
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
                Log::error("WhatsApp Profile Update Failed for team {$team->id}: " . json_encode($response));
                $this->dispatch('notify', title: 'Update Failed', message: 'Failed to update business profile: ' . $errorMessage, type: 'error');
            }
        } catch (\Exception $e) {
            Log::error("Failed to update WhatsApp business profile for team {$team->id}: " . $e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'An error occurred while updating the business profile: ' . $e->getMessage(), type: 'error');
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

                if (!($response['success'] ?? false)) {
                    // Some other Meta API failure — saved locally but warn about sync.
                    $msg = $response['message'] ?? ($response['error']['message'] ?? 'Unknown Meta API error');
                    $this->dispatch(
                        'notify',
                        title: 'Saved (Meta Sync Failed)',
                        message: 'Settings saved locally. Meta sync failed: ' . $msg,
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
            Log::error("Failed to update Business Behavior: " . $e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'Failed to save behavior settings: ' . $e->getMessage(), type: 'error');
        }
    }

    public function getTimezonesProperty()
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function handleEmbeddedSuccess($accessToken, $wabaId)
    {
        Log::debug("WhatsApp Setup: Received handleEmbeddedSuccess", [
            'waba_id' => $wabaId,
            'token_prefix' => substr($accessToken, 0, 8) . '...'
        ]);

        try {
            DB::beginTransaction();

            // Check for duplicate WABA usage in Trial teams (Abuse Protection)
            $duplicate = \App\Models\Team::where('whatsapp_business_account_id', $wabaId)
                ->where('id', '!=', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)
                ->whereIn('subscription_status', ['trial', 'expired'])
                ->exists();

            if ($duplicate) {
                Log::warning("WhatsApp Setup: Duplicate WABA detected", ['waba_id' => $wabaId]);
                throw new \Exception("This WhatsApp account has already been used for a trial subscription.");
            }

            // 1. Exchange for Long-Lived Token
            $exchangeResult = $this->exchangeForLongLivedToken($accessToken);
            if (!$exchangeResult['status']) {
                Log::error("WhatsApp Setup: Token exchange failed during embedded signup", ['error' => $exchangeResult['message']]);
                throw new \Exception("Token Exchange Failed: " . $exchangeResult['message']);
            }

            $longLivedToken = $exchangeResult['access_token'];
            $expiresIn = $exchangeResult['expires_in'] ?? null;
            $expiresAt = $expiresIn ? now()->addSeconds($expiresIn) : now()->addDays(60);

            Log::debug("WhatsApp Setup: Long-lived token acquired", ['expires_at' => $expiresAt]);

            // 2. Pre-Save to Team for subsequent calls
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
            $team->update([
                'whatsapp_access_token' => $longLivedToken,
                'whatsapp_business_account_id' => $wabaId,
                'whatsapp_token_expires_at' => $expiresAt,
                'whatsapp_token_last_validated' => now(),
            ]);

            // 3. Complete connection sequence
            $this->wm_business_account_id = $wabaId;
            $this->wm_access_token = $longLivedToken;

            // Converge on connect()
            $this->connect();

            DB::commit();
            $this->dispatch('notify', 'WhatsApp Account Connected Successfully!');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("WhatsApp Embedded Setup Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch(
                'notify',
                title: 'Link Failed',
                message: 'Could not link WhatsApp account: ' . $e->getMessage(),
                type: 'error'
            );
            // Re-load settings to clear partial state
            $this->loadSettings();
        }
    }

    public function connect()
    {
        Log::debug("WhatsApp Setup: Entering connect()", [
            'waba_id' => $this->wm_business_account_id
        ]);

        $rules = [
            'wm_business_account_id' => 'required',
        ];

        // If specific team/user doesn't have a token yet, require it.
        // Or if we are switching accounts, we likely need a new token.
        if (empty(\Illuminate\Support\Facades\Auth::user()->currentTeam->whatsapp_access_token)) {
            $rules['wm_access_token'] = 'required';
        }

        $this->validate($rules);

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

        // Start audit trail
        $auditId = $this->startAudit('connect');

        try {
            // Use transaction only if not already started by handleEmbeddedSuccess
            $startedTransaction = false;
            if (DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            }

            // [FIX] Cross-Linking: If switching WABA, clear the old phone number match
            if ($team->whatsapp_business_account_id && $team->whatsapp_business_account_id !== $this->wm_business_account_id) {
                Log::info("WhatsApp Setup: WABA mismatch, clearing phone ID", [
                    'old' => $team->whatsapp_business_account_id,
                    'new' => $this->wm_business_account_id
                ]);
                $team->whatsapp_phone_number_id = null;
                $this->wm_default_phone_number_id = null;
            }

            // Check for duplicate WABA usage in Trial teams (Abuse Protection)
            $duplicate = \App\Models\Team::where('whatsapp_business_account_id', $this->wm_business_account_id)
                ->where('id', '!=', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)
                ->whereIn('subscription_status', ['trial', 'expired'])
                ->exists();

            if ($duplicate) {
                Log::warning("WhatsApp Setup: Duplicate WABA detected", ['waba_id' => $this->wm_business_account_id]);
                throw new \Exception("This WhatsApp account has already been used for a trial subscription.");
            }

            $phoneIdToSave = $this->wm_default_phone_number_id ?: $team->whatsapp_phone_number_id;

            Log::debug("WhatsApp Setup: Updating team details", [
                'waba_id' => $this->wm_business_account_id,
                'phone_id' => $phoneIdToSave
            ]);

            $team->update([
                'whatsapp_business_account_id' => $this->wm_business_account_id,
                'whatsapp_app_id' => ($this->wm_fb_app_id !== get_setting('whatsapp_wm_fb_app_id')) ? $this->wm_fb_app_id : null,
                'whatsapp_verify_token' => $this->webhook_verify_token,
                'whatsapp_access_token' => $this->wm_access_token ?: $team->whatsapp_access_token,
                'whatsapp_phone_number_id' => $phoneIdToSave,
                'whatsapp_connected' => true,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::AUTHENTICATED,
            ]);

            // [NEW] Automatically fetch and store Facebook Business ID
            $token = $this->wm_access_token ?: $team->whatsapp_access_token;
            if ($token) {
                $fbBusinessId = $this->getFacebookBusinessId($this->wm_business_account_id, $token);
                if ($fbBusinessId) {
                    $team->update(['facebook_business_id' => $fbBusinessId]);
                    Log::info("WhatsApp Config: Stored Facebook Business ID {$fbBusinessId} for Team {$team->id}");
                } else {
                    Log::warning("WhatsApp Config: Could not fetch Facebook Business ID for WABA {$this->wm_business_account_id}");
                }
            }

            // 1. Automate Webhook Subscription (Links App to WABA)
            Log::debug("WhatsApp Setup: Subscribing to webhooks");
            $subResult = $this->subscribeToWebhooks($this->wm_business_account_id, $token);
            if (!$subResult['status']) {
                // Critical failure - if we can't subscribe, we shouldn't connect
                throw new \Exception("Webhook Subscription Failed: " . $subResult['message']);
            }

            // 2. Try to sync Templates (Functional check)
            Log::debug("WhatsApp Setup: Attempting initial template sync");
            $response = $this->loadTemplatesFromWhatsApp($this->wm_business_account_id, $token);

            if ($response['status']) {
                Log::debug("WhatsApp Setup: Templates synced successfully", ['count' => $response['count'] ?? 0]);
                // Handle Phone Numbers with auto-discovery if missing
                if (!empty($response['phone_numbers'])) {
                    $this->available_phone_numbers = $response['phone_numbers'];
                    $apiPhones = $response['phone_numbers'];
                    $firstPhone = $apiPhones[0];

                    if (empty($this->wm_default_phone_number_id)) {
                        $potentialId = $firstPhone['id'];
                        // [FIX] Check uniqueness for Auto-Discovery
                        $taken = \App\Models\Team::where('whatsapp_phone_number_id', $potentialId)
                            ->where('id', '!=', $team->id)
                            ->exists();

                        if (!$taken) {
                            Log::info("WhatsApp Setup: Auto-discovered phone ID: {$potentialId}");
                            $this->wm_default_phone_number_id = $potentialId;
                            $team->update(['whatsapp_phone_number_id' => $this->wm_default_phone_number_id]);
                        } else {
                            Log::warning("WhatsApp Setup: Auto-discovery skipped: Phone {$potentialId} is taken by another team.");
                            $this->dispatch('notify', title: 'Phone Number In Use', message: 'The default phone number is linked to another team. Please select manually.', type: 'warning');
                        }
                    }
                }
            } else {
                Log::error("WhatsApp Setup: Initial template sync failed", ['error' => $response['message']]);
                throw new \Exception("Initial Template Sync Failed: " . $response['message']);
            }

            $this->completeAudit($auditId, 'completed');

            if ($startedTransaction) {
                DB::commit();
            }

            // Run Verification Engine to determine final state (PROVISIONED or READY)
            Log::debug("WhatsApp Setup: Running verification engine");
            $this->validateConnection();

            // [FIX] Populate full account details (Quality, Limit, Display Name)
            $this->syncInfo(); // This will also call loadBusinessProfile() internally

            $this->loadSettings();
            // $this->loadBusinessProfile(); // Redundant if inside syncInfo()
            $this->refreshHealth();

        } catch (\Throwable $e) {
            if ($startedTransaction) {
                DB::rollBack();
            }
            $this->completeAudit($auditId, 'failed', ['error' => $e->getMessage()]);

            // Revert connected flag and state
            $team->update([
                'whatsapp_connected' => false,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::DISCONNECTED,
            ]);
            $this->is_whatsmark_connected = false;

            Log::error("WhatsApp Setup: Connection Flow Failed", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', title: 'Connection Failed', message: $e->getMessage(), type: 'error');

            if (!$startedTransaction) {
                // If we are in embedded flow (parent transaction), we generally want to bubble up, 
                // BUT for this specific 500 debugging, we want to catch it to see the error.
                // Re-throwing Throwable might just cause the 500 again if not caught upstream.
                // Let's log it and NOT re-throw for now to ensure UI feedback.
                // throw $e; 
            }
        }
    }

    public function validateConnection()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        $engine = app(\App\Services\WhatsAppVerificationEngine::class)->setTeam($team);

        $result = $engine->verify();

        $this->loadSettings(); // Reload to get fresh state details

        if ($result['state']->value !== 'ready') {
            $this->dispatch('notify', 'Warning: Connection verified with issues: ' . $result['state']->label());
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
            'outbound_webhook_url' => 'nullable|url'
        ]);

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;

        if (!$team) {
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
            $this->is_webhook_connected = !empty($this->outbound_webhook_url);

            $this->dispatch('notify', title: 'Webhook Saved', message: 'Outbound webhook URL updated successfully.', type: 'success');
        } catch (\Exception $e) {
            Log::error("Failed to update outbound webhook for team {$team->id}: " . $e->getMessage());
            $this->dispatch('notify', title: 'Error', message: 'Failed to save webhook URL: ' . $e->getMessage(), type: 'error');
        }
    }

    public function setupWebhook()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam;
        if (!$team->whatsapp_access_token || !$this->wm_business_account_id) {
            $this->dispatch('notify', 'Missing configuration. Please connect first.');
            return;
        }

        $result = $this->subscribeToWebhooks($this->wm_business_account_id, $team->whatsapp_access_token);

        if ($result['status']) {
            $this->dispatch('notify', 'Webhook subscribed successfully!');
            $this->refreshHealth();
        } else {
            $this->dispatch('notify', 'Webhook subscription failed: ' . $result['message']);
        }
    }

    public function syncInfo()
    {
        if (!$this->wm_default_phone_number_id) {
            $this->dispatch('notify', 'No Phone Number ID configured. Please connect first.');
            return;
        }

        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();

        if (!$team->whatsapp_business_account_id || !$team->whatsapp_access_token) {
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
            $this->dispatch('notify', 'Sync failed: ' . $result['message']);
        }
        $this->refreshHealth();
    }

    /**
     * Fetch and persist the Business Verification Status from Meta.
     */
    public function checkBusinessVerification()
    {
        $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();

        if (!$team->whatsapp_business_account_id || !$team->whatsapp_access_token) {
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
            Log::error('WhatsApp Config: Exception while checking business verification: ' . $e->getMessage());
        }
    }

    public function refreshHealth()
    {
        if (!$this->is_whatsmark_connected) {
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
                    : ($team->whatsapp_access_token ? "Connected" : 'Not connected'),
                'icon' => 'key'
            ],
            [
                'id' => 'select_phone',
                'title' => 'Select Phone Number',
                'status' => $team->whatsapp_phone_number_id
                    ? (in_array($state?->value, ['authenticated']) ? 'pending' : 'completed')
                    : 'not_started',
                'description' => $this->wm_phone_display ?: 'No phone selected',
                'icon' => 'phone'
            ],
            [
                'id' => 'configure_profile',
                'title' => 'Configure Business Profile',
                'status' => $this->profile_description ? 'completed' : 'not_started',
                'description' => $this->profile_description ? 'Profile description set' : 'Profile incomplete',
                'icon' => 'user-circle'
            ],
            [
                'id' => 'webhook_setup',
                'title' => 'Webhook Setup',
                'status' => $state && in_array($state->value, ['ready', 'ready_warning', 'restricted']) ? 'completed' : 'not_started',
                'description' => $state && in_array($state->value, ['ready', 'ready_warning', 'restricted']) ? 'Receiving events' : 'Not configured',
                'icon' => 'webhook'
            ],
            [
                'id' => 'sync_templates',
                'title' => 'Sync Message Templates',
                'status' => $team->whatsappTemplates()->count() > 0 ? 'completed' : 'not_started',
                'description' => $team->whatsappTemplates()->count() . ' templates synced',
                'icon' => 'template'
            ],
            [
                'id' => 'system_ready',
                'title' => 'Messaging Ready',
                'status' => in_array($state?->value, ['ready', 'ready_warning']) ? 'completed' : ($state?->value === 'restricted' ? 'warning' : 'not_started'),
                'description' => $state ? $state->label() : 'Pending verification',
                'icon' => 'check-circle'
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
        if (!$this->wm_default_phone_number_id) {
            $this->dispatch('notify', 'No Phone Number ID found.');
            return;
        }

        $this->validate([
            'registrationPin' => 'required|digits:6'
        ]);

        $result = $this->registerPhone($this->wm_default_phone_number_id, $this->registrationPin);

        if ($result['status']) {
            $this->dispatch('notify', 'Phone number registered successfully.');
            // Re-sync info after registration just in case
            $this->syncInfo();
        } else {
            $this->dispatch('notify', 'Registration failed: ' . $result['message']);
        }
    }

    public function loadBusinessProfile()
    {
        try {
            $team = \Illuminate\Support\Facades\Auth::user()->currentTeam->fresh();
            if (!$team->whatsapp_access_token || !$team->whatsapp_phone_number_id) {
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
                \Illuminate\Support\Facades\Log::error("WhatsApp Profile API Error: " . json_encode($response['error']));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to load WhatsApp Business Profile: " . $e->getMessage());
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
        if (!$this->wm_business_account_id)
            return;

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
                if (!$token)
                    return;

                $wabaId = $this->wm_business_account_id;
                $url = "https://graph.facebook.com/" . $this->getApiVersion() . "/{$wabaId}/phone_numbers";
                $response = \Illuminate\Support\Facades\Http::withToken($token)->get($url);

                if ($response->successful()) {
                    $this->available_phone_numbers = $response->json('data') ?? [];
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to load available phone numbers: " . $e->getMessage());
        }
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
                'whatsapp_setup_state' => \App\Enums\IntegrationState::DISCONNECTED // Partial disconnect
            ]);
            Log::warning("Phone ID $phoneId reclaimed from Team {$existing->id} by Team {$team->id}");
        }

        $this->wm_default_phone_number_id = $phoneId;
        $this->wm_phone_display = $displayPhone;

        $team->update([
            'whatsapp_phone_number_id' => $phoneId
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
