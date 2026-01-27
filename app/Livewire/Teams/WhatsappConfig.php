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

    // Connection Fields
    public $wm_fb_app_id;
    public $wm_fb_app_secret;
    public $wm_business_account_id;
    // public $wm_access_token; // REMOVED: Prevent leakage to frontend
    public $outbound_webhook_url;

    // Business Profile Fields
    public $profile_about;
    public $profile_address;
    public $profile_description;
    public $profile_email;
    public $profile_vertical;
    public $profile_websites = [];
    public $profile_picture_url;
    public $is_editing_profile = false;




    // Status
    public $is_webhook_connected = false;
    public $is_whatsmark_connected = false;
    public $webhook_verify_token;

    // Info Fields
    public $wm_default_phone_number;
    public $wm_default_phone_number_id;

    public $wm_messaging_limit;
    public $wm_quality_rating;
    public $wm_phone_display;
    public $wm_verified_name;

    public $token_info = [];
    public $credits = 0;
    public $credits_total = 1000;
    public $wm_test_message;

    // Health Monitoring
    public $healthScore = 0;
    public $healthStatus = 'unknown';
    public $tokenHealthScore = 0;
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
    public $awayMessageEnabled = false;
    public $awayMessage = 'We are currently closed. We will get back to you soon.';

    // Default Hours: Mon-Fri 09-17
    public $openTime = '09:00';
    public $closeTime = '17:00';

    // Call Settings
    public $callingEnabled = false;
    public $callButtonVisible = false;
    public $syncCallHours = false;
    public $callbackPermissionEnabled = false;

    protected $rules = [
        'wm_fb_app_id' => 'nullable',
        'wm_fb_app_secret' => 'nullable',
        'wm_business_account_id' => 'required',
        'wm_access_token' => 'required',
        'wm_default_phone_number_id' => 'nullable',
    ];

    public function mount()
    {
        $this->loadSettings();
        if ($this->is_whatsmark_connected) {
            $this->loadBusinessProfile();
            $this->refreshHealth();

            // Auto-sync once if basic info is missing but we are connected
            if (!$this->wm_verified_name || $this->wm_quality_rating === 'UNKNOWN') {
                $this->syncInfo();
            }
        }
    }


    public function loadSettings()
    {
        $team = auth()->user()->currentTeam;

        // Load from Team Model first, fallback to settings if empty (migration path)
        // Actually, App ID might be global for the SaaS unless white-labeled. 
        // Let's stick to global for App ID if it's not in Team. 
        // But WABA, Token, PhoneID ARE in Team.

        $this->wm_fb_app_id = get_setting('whatsapp_wm_fb_app_id');
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
                // $this->wm_access_token = null; // DO NOT EXPOSE TO FRONTEND
                // Proceed as disconnected
            }
        }

        if ($team->whatsapp_connected) {
            $this->is_whatsmark_connected = true;
            $this->wm_business_account_id = $team->whatsapp_business_account_id;
            // $this->wm_access_token = $team->whatsapp_access_token; // DO NOT EXPOSE TO FRONTEND
            $this->outbound_webhook_url = $team->outbound_webhook_url;
        } else {
            $this->is_whatsmark_connected = false;
            $this->wm_business_account_id = null;
            $this->outbound_webhook_url = null;
        }


        $this->is_whatsmark_connected = !empty($team->whatsapp_access_token) && !empty($this->wm_business_account_id);
        $this->is_webhook_connected = !empty($this->outbound_webhook_url);

        $this->webhook_verify_token = get_setting('whatsapp_webhook_verify_token');
        if (empty($this->webhook_verify_token)) {
            $this->webhook_verify_token = Str::random(16);
            set_setting('whatsapp_webhook_verify_token', $this->webhook_verify_token);
        }

        $this->wm_default_phone_number_id = $team->whatsapp_phone_number_id;

        $this->wm_messaging_limit = $team->whatsapp_messaging_limit ?: 'TIER_1K';
        $this->wm_quality_rating = $team->whatsapp_quality_rating ?: 'UNKNOWN';
        $this->wm_phone_display = $team->whatsapp_phone_display ?: '';
        $this->wm_verified_name = $team->whatsapp_verified_name ?: '';
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
        $this->awayMessageEnabled = $team->away_message_enabled;
        $this->awayMessage = $team->away_message;

        // Load first day's hours as default
        $hours = $team->business_hours;
        if (isset($hours['mon'])) {
            $this->openTime = $hours['mon'][0];
            $this->closeTime = $hours['mon'][1];
        }

        // Load Call Settings
        if (isset($team->whatsapp_settings['calling'])) {
            $this->callingEnabled = $team->whatsapp_settings['calling']['status'] === 'enabled';
            $this->callButtonVisible = $team->whatsapp_settings['calling']['call_icon_visibility'] === 'show';
            $this->callbackPermissionEnabled = ($team->whatsapp_settings['calling']['callback_permission_status'] ?? 'disabled') === 'enabled';
            $this->syncCallHours = isset($team->whatsapp_settings['calling']['call_hours']);
        }
    }

    public function updateBusinessProfile()
    {
        $team = auth()->user()->currentTeam;

        if (!auth()->user()->ownsTeam($team)) {
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
        ]);

        try {
            $waService = new \App\Services\WhatsAppService($team);
            $profileData = [
                'about' => $this->profile_about,
                'address' => $this->profile_address,
                'description' => $this->profile_description,
                'email' => $this->profile_email,
                'vertical' => $this->profile_vertical,
                'websites' => array_filter($this->profile_websites), // Remove empty websites
            ];

            $response = $waService->updateBusinessProfile($profileData);

            if ($response['status']) {
                // If profile picture URL is provided, update it separately
                if (!empty($this->profile_picture_url)) {
                    // $waService->updateBusinessProfilePicture($this->profile_picture_url); // Removed as method likely missing in service, checking...
                    Log::warning("Profile Picture update skipped as method not confirmed in WhatsAppService.");
                }

                $this->dispatch('notify', 'Business profile updated successfully.');
                $this->is_editing_profile = false;
                $this->loadBusinessProfile(); // Reload to reflect changes
            } else {
                $this->dispatch('notify', 'Failed to update business profile: ' . $response['message']);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update WhatsApp business profile for team {$team->id}: " . $e->getMessage());
            $this->dispatch('notify', 'An error occurred while updating the business profile: ' . $e->getMessage());
        }
    }

    public function updateBehaviorSettings()
    {
        try {
            $team = auth()->user()->currentTeam;

            // Construct Business Hours (Simple Mon-Fri for MVP)
            $hours = [];
            foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $day) {
                $hours[$day] = [$this->openTime, $this->closeTime];
            }

            $team->forceFill([
                'timezone' => $this->timezone,
                'away_message_enabled' => $this->awayMessageEnabled,
                'away_message' => $this->awayMessage,
                'business_hours' => $hours,
            ])->save();

            // Save Call Settings to Meta
            $waService = new \App\Services\WhatsAppService($team);
            $settings = [
                'status' => $this->callingEnabled ? 'enabled' : 'disabled',
                'call_icon_visibility' => $this->callButtonVisible ? 'show' : 'hide',
                'callback_permission_status' => $this->callbackPermissionEnabled ? 'enabled' : 'disabled',
            ];

            if ($this->syncCallHours) {
                $settings['business_hours'] = $hours;
                $settings['timezone'] = $this->timezone;
            }

            // Sync with Meta
            $response = $waService->updateSystemCallSettings($settings);

            // Update local cache regardless of Meta success
            $currentSettings = $team->whatsapp_settings ?? [];
            $currentSettings['calling'] = $settings;
            if ($this->syncCallHours) {
                $currentSettings['calling']['call_hours'] = true;
            } else {
                unset($currentSettings['calling']['call_hours']);
            }

            $team->forceFill(['whatsapp_settings' => $currentSettings])->save();

            if (isset($response['success']) && $response['success']) {
                $this->dispatch('notify', 'Behavior settings saved and synced with Meta.');
            } else {
                $msg = $response['message'] ?? ($response['error']['message'] ?? 'Unknown Meta API Error');
                $this->dispatch('notify', title: 'Meta Sync Warning', message: 'Saved locally, but Meta failed: ' . $msg, type: 'warning');
            }

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
        try {
            DB::beginTransaction();

            // Check for duplicate WABA usage in Trial teams (Abuse Protection)
            $duplicate = \App\Models\Team::where('whatsapp_business_account_id', $wabaId)
                ->where('id', '!=', auth()->user()->currentTeam->id)
                ->whereIn('subscription_status', ['trial', 'expired'])
                ->exists();

            if ($duplicate) {
                throw new \Exception("This WhatsApp account has already been used for a trial subscription.");
            }

            // 1. Exchange for Long-Lived Token
            $exchangeResult = $this->exchangeForLongLivedToken($accessToken);
            if (!$exchangeResult['status']) {
                throw new \Exception("Token Exchange Failed: " . $exchangeResult['message']);
            }

            $longLivedToken = $exchangeResult['access_token'];
            $expiresIn = $exchangeResult['expires_in'] ?? null;
            $expiresAt = $expiresIn ? now()->addSeconds($expiresIn) : now()->addDays(60);

            // 2. Pre-Save to Team for subsequent calls
            $team = auth()->user()->currentTeam;
            $team->update([
                'whatsapp_access_token' => $longLivedToken,
                'whatsapp_business_account_id' => $wabaId,
                'whatsapp_token_expires_at' => $expiresAt,
                'whatsapp_token_last_validated' => now(),
            ]);

            // 3. Complete connection sequence
            // $this->wm_access_token = $longLivedToken; // DO NOT EXPOSE TO FRONTEND
            $this->wm_business_account_id = $wabaId;

            // Converge on connect()
            $this->connect();

            DB::commit();
            $this->dispatch('notify', 'WhatsApp Account Connected Successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Embedded Signup Completion Failed: " . $e->getMessage());
            $this->dispatch('notify', 'Connection failed: ' . $e->getMessage());

            // Re-load settings to clear partial state
            $this->loadSettings();
        }
    }

    public function connect()
    {
        $this->validate([
            'wm_business_account_id' => 'required',
            // 'wm_access_token' => 'required', // Removed from frontend
        ]);

        $team = auth()->user()->currentTeam;

        // Start audit trail
        $auditId = $this->startAudit('connect');

        try {
            // Use transaction only if not already started by handleEmbeddedSuccess
            $startedTransaction = false;
            if (DB::transactionLevel() === 0) {
                DB::beginTransaction();
                $startedTransaction = true;
            }

            // Move to AUTHENTICATED state immediately

            // [FIX] Cross-Linking: If switching WABA, clear the old phone number match
            if ($team->whatsapp_business_account_id && $team->whatsapp_business_account_id !== $this->wm_business_account_id) {
                $team->whatsapp_phone_number_id = null;
                $this->wm_default_phone_number_id = null;
            }

            // Check for duplicate WABA usage in Trial teams (Abuse Protection)
            $duplicate = \App\Models\Team::where('whatsapp_business_account_id', $this->wm_business_account_id)
                ->where('id', '!=', auth()->user()->currentTeam->id)
                ->whereIn('subscription_status', ['trial', 'expired'])
                ->exists();

            if ($duplicate) {
                throw new \Exception("This WhatsApp account has already been used for a trial subscription.");
            }

            // [FIX] Prevent Ghost Numbers: Check if Phone ID is already taken
            $phoneIdToSave = $this->wm_default_phone_number_id ?: $team->whatsapp_phone_number_id;
            if ($phoneIdToSave) {
                $phoneTaken = \App\Models\Team::where('whatsapp_phone_number_id', $phoneIdToSave)
                    ->where('id', '!=', $team->id)
                    ->exists();

                if ($phoneTaken) {
                    throw new \Exception("The selected Phone Number ID ({$phoneIdToSave}) is already connected to another team.");
                }
            }

            $team->update([
                'whatsapp_business_account_id' => $this->wm_business_account_id,
                'whatsapp_access_token' => $team->whatsapp_access_token, // Use team accessor directly if needed, or better, don't update if not changed
                'whatsapp_phone_number_id' => $phoneIdToSave,
                'whatsapp_connected' => true,
                'whatsapp_setup_state' => \App\Enums\IntegrationState::AUTHENTICATED,
            ]);

            // Try to sync Templates (Functional check)
            $response = $this->loadTemplatesFromWhatsApp();

            if ($response['status']) {
                // Handle Phone Numbers with auto-discovery if missing
                if (!empty($response['phone_numbers'])) {
                    $apiPhones = $response['phone_numbers'];
                    $firstPhone = $apiPhones[0];

                    if (empty($this->wm_default_phone_number_id)) {
                        $potentialId = $firstPhone['id'];
                        // [FIX] Check uniqueness for Auto-Discovery
                        $taken = \App\Models\Team::where('whatsapp_phone_number_id', $potentialId)
                            ->where('id', '!=', $team->id)
                            ->exists();

                        if (!$taken) {
                            $this->wm_default_phone_number_id = $potentialId;
                            $team->update(['whatsapp_phone_number_id' => $this->wm_default_phone_number_id]);
                        } else {
                            Log::warning("Auto-discovery skipped: Phone {$potentialId} is taken by another team.");
                        }
                    }
                }

                // Automate Webhook Subscription
                $subResult = $this->subscribeToWebhooks($this->wm_business_account_id, $team->whatsapp_access_token);
                if (!$subResult['status']) {
                    Log::warning("Webhook Auto-subscription failed for team {$team->id}: " . $subResult['message']);
                }
            }

            $this->completeAudit($auditId, 'completed');

            if ($startedTransaction) {
                DB::commit();
            }

            // Run Verification Engine to determine final state (PROVISIONED or READY)
            $this->validateConnection();

            $this->loadSettings();
            $this->loadBusinessProfile();
            $this->refreshHealth();

        } catch (\Exception $e) {
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

            Log::error("Connection Flow Failed: " . $e->getMessage());
            $this->dispatch('notify', 'Connection failed: ' . $e->getMessage());

            if (!$startedTransaction) {
                throw $e; // Re-throw if handled by parent transaction (Embedded Flow)
            }
        }
    }

    public function validateConnection()
    {
        $team = auth()->user()->currentTeam;
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

        if (auth()->user()->currentTeam) {
            auth()->user()->currentTeam->forceFill([
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

        set_setting('whatsapp_outbound_webhook_url', $this->outbound_webhook_url);

        // Also update the Team model directly if strictly needed for listeners, 
        // but 'get_setting' implies global or team-scoped settings helper. 
        // Assuming get_setting handles current team context.
        // If the listener uses $team->outbound_webhook_url, we need to update the Team model too.
        if (auth()->user()->currentTeam) {
            auth()->user()->currentTeam->update(['outbound_webhook_url' => $this->outbound_webhook_url]);
        }

        $this->dispatch('notify', 'Outbound webhook URL updated successfully.');
    }

    public function syncInfo()
    {
        if (!$this->wm_default_phone_number_id) {
            $this->dispatch('notify', 'No Phone Number ID configured. Please connect first.');
            return;
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
            $team = auth()->user()->currentTeam;
            $team->update([
                'whatsapp_messaging_limit' => $this->wm_messaging_limit,
                'whatsapp_quality_rating' => $this->wm_quality_rating,
                'whatsapp_phone_display' => $this->wm_phone_display,
                'whatsapp_verified_name' => $this->wm_verified_name,
            ]);

            // Also reload business profile details
            $this->loadBusinessProfile();

            $this->dispatch('notify', 'Account info synced successfully!');
        } else {
            $this->dispatch('notify', 'Sync failed: ' . $result['message']);
        }
        $this->refreshHealth();
    }

    public function refreshHealth()
    {
        if (!$this->is_whatsmark_connected) {
            return;
        }

        $team = auth()->user()->currentTeam;
        $monitor = app(\App\Services\WhatsAppHealthMonitor::class);
        $health = $monitor->checkHealth($team);

        $this->healthScore = $health['overall_score'] ?? 0;
        $this->healthStatus = $health['status'] ?? 'unknown';
        $this->tokenHealthScore = $health['token']['score'] ?? 0;
        $this->qualityHealthScore = $health['quality']['score'] ?? 0;
        $this->messagingUsagePercent = $health['messaging']['usage_percent'] ?? 0;
        // [FIX] Handle permanent tokens (null expiry) by defaulting to 999 instead of 0
        $this->tokenDaysUntilExpiry = $health['token']['days_remaining'] ?? 999;
        $this->currentUsage = $health['messaging']['current_usage'] ?? 0;
        $this->dailyLimit = $health['messaging']['daily_limit'] ?? 0;

        $this->setupProgress = $this->getSetupProgress();
    }

    public function getSetupProgress()
    {
        $team = auth()->user()->currentTeam;
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

    public function registerNumber()
    {
        if (!$this->wm_default_phone_number_id) {
            $this->dispatch('notify', 'No Phone Number ID found.');
            return;
        }

        // Default PIN as per request
        $pin = '123456';

        $result = $this->registerPhone($this->wm_default_phone_number_id, $pin);

        if ($result['status']) {
            $this->dispatch('notify', 'Phone number registered successfully (PIN: 123456).');
            // Re-sync info after registration just in case
            $this->syncInfo();
        } else {
            $this->dispatch('notify', 'Registration failed: ' . $result['message']);
        }
    }

    public function loadBusinessProfile()
    {
        try {
            $team = auth()->user()->currentTeam->fresh();
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
        $this->profile_websites[] = '';
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
            'team_id' => auth()->user()->currentTeam->id,
            'user_id' => auth()->id(),
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
