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
    public $tokenDaysUntilExpiry = 0;
    public $currentUsage = 0;
    public $dailyLimit = 0;
    public $setupProgress = [];
    public $integrationState = 'disconnected';
    public $integrationStateLabel = 'Disconnected';
    public $integrationStateColor = 'slate';

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

        $this->wm_business_account_id = $team->whatsapp_business_account_id;
        $this->wm_access_token = $team->whatsapp_access_token;
        $this->outbound_webhook_url = $team->outbound_webhook_url;

        $this->is_whatsmark_connected = !empty($this->wm_access_token) && !empty($this->wm_business_account_id);
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

        $this->integrationState = $team->whatsapp_setup_state?->value ?? 'disconnected';
        $this->integrationStateLabel = $team->whatsapp_setup_state?->label() ?? 'Disconnected';
        $this->integrationStateColor = $team->whatsapp_setup_state?->color() ?? 'slate';

        // Fetch Real Billing Data
        $wallet = \App\Models\TeamWallet::firstOrCreate(['team_id' => $team->id]);
        $this->credits = $wallet->balance;

        $plan = \App\Models\Plan::where('name', $team->subscription_plan)->first();
        $this->credits_total = $plan ? $plan->message_limit : 1000;
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
            $this->wm_access_token = $longLivedToken;
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
            'wm_access_token' => 'required',
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

            // Check for duplicate WABA usage in Trial teams (Abuse Protection)
            $duplicate = \App\Models\Team::where('whatsapp_business_account_id', $this->wm_business_account_id)
                ->where('id', '!=', auth()->user()->currentTeam->id)
                ->whereIn('subscription_status', ['trial', 'expired'])
                ->exists();

            if ($duplicate) {
                throw new \Exception("This WhatsApp account has already been used for a trial subscription.");
            }

            $team->update([
                'whatsapp_business_account_id' => $this->wm_business_account_id,
                'whatsapp_access_token' => $this->wm_access_token,
                'whatsapp_phone_number_id' => $this->wm_default_phone_number_id ?: $team->whatsapp_phone_number_id,
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
                        $this->wm_default_phone_number_id = $firstPhone['id'];
                        $team->update(['whatsapp_phone_number_id' => $this->wm_default_phone_number_id]);
                    }
                }

                // Automate Webhook Subscription
                $subResult = $this->subscribeToWebhooks($this->wm_business_account_id, $this->wm_access_token);
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

    public function disconnect()
    {
        $this->is_whatsmark_connected = false;
        $this->is_webhook_connected = false;
        $this->wm_access_token = '';

        if (auth()->user()->currentTeam) {
            auth()->user()->currentTeam->forceFill([
                'whatsapp_access_token' => null,
                'whatsapp_business_account_id' => null,
                'whatsapp_phone_number_id' => null,
                'whatsapp_connected' => false,
                'whatsapp_token_expires_at' => null,
            ])->save();
        }

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
        $this->tokenDaysUntilExpiry = $health['token']['days_remaining'] ?? 0;
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

    public function updateBusinessProfile()
    {
        try {
            $service = app(\App\Services\WhatsAppService::class);
            $service->setTeam(auth()->user()->currentTeam);

            $data = [
                'about' => $this->profile_about,
                'address' => $this->profile_address,
                'description' => $this->profile_description,
                'email' => $this->profile_email,
                'vertical' => $this->profile_vertical,
                'websites' => $this->profile_websites,
            ];

            $response = $service->updateBusinessProfile($data);

            if (isset($response['success']) && $response['success']) {
                $this->is_editing_profile = false;
                $this->dispatch('notify', 'Business profile updated successfully!');
            } else {
                $error = $response['error']['message'] ?? 'Unknown error';
                $this->dispatch('notify', 'Failed to update profile: ' . $error);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Error: ' . $e->getMessage());
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
