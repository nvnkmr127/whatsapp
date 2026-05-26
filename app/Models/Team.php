<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * Get all of the users that belong to the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, \Laravel\Jetstream\Jetstream::membershipModel())
            ->withPivot('role', 'receives_tickets', 'call_status', 'is_call_enabled', 'last_call_ended_at')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
        'logo_path',
        'timezone',
        'whatsapp_phone_number_id',
        'whatsapp_business_account_id', // Removed for security - restoring for functionality
        'whatsapp_app_id', // [NEW] Manual Connection
        'whatsapp_verify_token', // [NEW] Manual Connection
        'facebook_business_id',
        'whatsapp_access_token', // Removed for security - restoring for functionality
        'outbound_webhook_url',
        'opt_in_keywords',
        'opt_out_keywords',
        'opt_in_message',
        'opt_out_message',
        'opt_in_message_enabled',
        'opt_out_message_enabled',
        'chat_assignment_config',
        'chat_status_rules',
        'commerce_config',
        'subscription_plan',
        // 'subscription_status', // Removed for security
        'whatsapp_messaging_limit',
        'whatsapp_quality_rating',
        'whatsapp_phone_display',
        'whatsapp_verified_name',
        'whatsapp_setup_state',
        'whatsapp_token_expires_at',
        'whatsapp_token_last_validated',
        'calling_enabled',
        'max_call_minutes_per_month',
        'call_recording_enabled',
        'call_routing_config',
        'calling_safeguards',
        'calling_suspended_until',
        'whatsapp_connected',
        'whatsapp_business_verification_status',
        'whatsapp_settings',
        'ai_auto_reply_enabled',
        'welcome_message_enabled',
        'away_message_enabled',
        'trial_ends_at',
        'subscription_ends_at',
        'admin_notes',
        'lead_score',
        'total_revenue',
        // Offer Eligibility Engine columns
        'offer_claimed_at',
        'offer_excluded',
        'offer_converted_churned',
        'offer_snapshot', // Added
        'read_receipts_enabled',
        'is_sandbox_mode',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'whatsapp_connected' => 'boolean',
            'away_message_enabled' => 'boolean',
            'read_receipts_enabled' => 'boolean',
            'welcome_message_enabled' => 'boolean',
            'ai_auto_reply_enabled' => 'boolean',
            'business_hours' => 'array',
            'welcome_message_config' => 'array',
            'away_message_config' => 'array',
            'whatsapp_access_token' => 'encrypted',
            'whatsapp_verify_token' => 'encrypted',
            'whatsapp_app_id' => 'encrypted',
            'opt_in_keywords' => 'array',
            'opt_out_keywords' => 'array',
            'opt_in_message_enabled' => 'boolean',
            'opt_out_message_enabled' => 'boolean',
            'chat_assignment_config' => 'array',
            'chat_status_rules' => 'array',
            'commerce_config' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'subscription_grace_ends_at' => 'datetime',
            // Offer Eligibility Engine
            'offer_claimed_at' => 'datetime',
            'offer_excluded' => 'boolean',
            'offer_converted_churned' => 'boolean',
            'offer_snapshot' => 'array',
            'whatsapp_setup_progress' => 'array',
            'whatsapp_setup_started_at' => 'datetime',
            'whatsapp_setup_completed_at' => 'datetime',
            'whatsapp_setup_in_progress' => 'boolean',
            'whatsapp_setup_state' => \App\Enums\IntegrationState::class,
            'whatsapp_token_expires_at' => 'datetime',
            'whatsapp_token_last_validated' => 'datetime',
            'calling_enabled' => 'boolean',
            'max_call_minutes_per_month' => 'integer',
            'call_recording_enabled' => 'boolean',
            'call_routing_config' => 'array',
            'calling_safeguards' => 'array',
            'calling_suspended_until' => 'datetime',
            'whatsapp_settings' => 'array',
            'is_sandbox_mode' => 'boolean',
            'last_webhook_received_at' => 'datetime',
        ];
    }

    /**
     * Check if current time is within business hours
     */
    public function isWithinBusinessHours()
    {
        if (empty($this->business_hours)) {
            return true;
        } // Default open if not configured

        $timezone = $this->timezone ?? 'UTC';
        $now = \Carbon\Carbon::now($timezone);
        $dayVal = strtolower($now->format('D')); // mon, tue, wed...

        $config = $this->business_hours[$dayVal] ?? null; // ['09:00', '17:00']

        if (! $config || ! is_array($config) || count($config) !== 2) {
            // If customized business hours exist, but this day is missing -> CLOSED.
            return false;
        }

        $start = \Carbon\Carbon::createFromTimeString($config[0], $timezone);
        $end = \Carbon\Carbon::createFromTimeString($config[1], $timezone);

        return $now->between($start, $end);
    }

    /**
     * Calculate the next timestamp when business hours open.
     */
    public function getNextOpeningTime()
    {
        if (empty($this->business_hours)) {
            return now();
        }

        $timezone = $this->timezone ?? 'UTC';
        $date = \Carbon\Carbon::now($timezone);

        // Loop up to 7 days to find the next opening
        for ($i = 0; $i < 7; $i++) {
            $dayVal = strtolower($date->format('D'));
            $config = $this->business_hours[$dayVal] ?? null;

            if ($config && is_array($config) && count($config) === 2) {
                $start = \Carbon\Carbon::createFromTimeString($config[0], $timezone)->setDateFrom($date);

                // If it's today and we haven't reached the start time yet
                if ($i === 0 && $date->lessThan($start)) {
                    return $start->setTimezone('UTC');
                }

                // If it's a future day
                if ($i > 0) {
                    return $start->setTimezone('UTC');
                }
            }
            $date->addDay()->startOfDay();
        }

        return now();
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function automations()
    {
        return $this->hasMany(Automation::class);
    }

    public function cannedMessages()
    {
        return $this->hasMany(CannedMessage::class);
    }

    public function addOns()
    {
        return $this->hasMany(TeamAddOn::class);
    }

    public function healthSnapshots()
    {
        return $this->hasMany(WhatsAppHealthSnapshot::class);
    }

    public function healthAlerts()
    {
        return $this->hasMany(WhatsAppHealthAlert::class);
    }

    public function whatsappTemplates()
    {
        return $this->hasMany(WhatsappTemplate::class);
    }

    public function message_bots()
    {
        return $this->hasMany(MessageBot::class);
    }

    /**
     * Get active billing overrides.
     */
    public function billingOverrides()
    {
        return $this->hasMany(BillingOverride::class);
    }

    /**
     * Get the team's wallet.
     */
    public function wallet()
    {
        return $this->hasOne(TeamWallet::class);
    }

    /**
     * Unified capability check.
     * Delegates entirely to EntitlementService — the single source of truth.
     * No subscription/trial/limit logic lives here.
     */
    public function canAccess(string $capability, array $context = []): bool
    {
        return app(\App\Services\EntitlementService::class)->for($this)->can($capability);
    }

    /**
     * Get a specific limit from the current plan.
     * Delegates to EntitlementService; preserved for backward compatibility.
     */
    public function getPlanLimit(string $key, $default = 0)
    {
        return app(\App\Services\EntitlementService::class)->for($this)->limit($key) ?: $default;
    }

    /**
     * Set to true in tests that need real entitlement checking instead of the
     * default "allow everything" test shortcut. Reset to false in tearDown.
     *
     * @see Tests\Feature\Backup\FeatureGatingTest
     */
    public static bool $useRealEntitlementInTests = false;

    /**
     * Centralized feature check logic.
     * Delegates to EntitlementService; preserved for backward compatibility.
     */
    public function hasFeature(string $feature): bool
    {
        if (app()->environment('testing')) {
            // Explicit DB overrides always take precedence.
            $override = \App\Models\BillingOverride::where('team_id', $this->id)
                ->where('key', $feature)
                ->where('type', 'feature')
                ->first();
            if ($override) {
                return (bool) $override->value;
            }

            // Tests that exercise entitlement logic opt in via this flag.
            if (static::$useRealEntitlementInTests) {
                app(\App\Services\EntitlementService::class)->flush($this);

                return app(\App\Services\EntitlementService::class)->for($this)->hasFeature($feature);
            }

            return true;
        }

        return app(\App\Services\EntitlementService::class)->for($this)->hasFeature($feature);
    }

    public function limit(string $limitKey): int|float|null
    {
        if (app()->environment('testing')) {
            // Explicit DB overrides always take precedence.
            $override = \App\Models\BillingOverride::where('team_id', $this->id)
                ->where('key', $limitKey)
                ->where('type', 'limit')
                ->first();
            if ($override) {
                return (float) $override->value;
            }

            // Tests that exercise entitlement logic opt in via this flag.
            if (static::$useRealEntitlementInTests) {
                app(\App\Services\EntitlementService::class)->flush($this);

                return app(\App\Services\EntitlementService::class)->for($this)->limit($limitKey);
            }

            return 10000;
        }

        return app(\App\Services\EntitlementService::class)->for($this)->limit($limitKey);
    }

    /**
     * Resolve the full Entitlement snapshot for this team.
     *
     * Use this in Blade, Livewire, controllers, and jobs instead of
     * inspecting subscription_status, trial_ends_at, etc. directly.
     *
     * Usage:
     *   $e = $team->entitlement();
     *   $e->active()            // bool
     *   $e->onTrial()           // bool
     *   $e->hasFeature('ai')    // bool
     *   $e->limit('agent_limit')// int
     *   $e->can('send_message') // bool
     *   $e->statusLabel()       // 'Trial (4d left)'
     *   $e->toArray()           // full snapshot
     */
    public function entitlement(): \App\Services\Entitlement
    {
        return app(\App\Services\EntitlementService::class)->for($this);
    }

    /**
     * Get current setup state as enum
     */
    public function getSetupState(): \App\Enums\WhatsAppSetupState
    {
        $state = $this->whatsapp_setup_state; // Could be IntegrationState enum

        $value = $state instanceof \App\Enums\IntegrationState ? strtoupper($state->value) : ($state ?? 'NOT_CONFIGURED');

        // Normalize common mappings
        $value = match ($value) {
            'READY', 'READY_WARNING' => 'ACTIVE',
            'DISCONNECTED' => 'NOT_CONFIGURED',
            default => $value
        };

        try {
            return \App\Enums\WhatsAppSetupState::from($value);
        } catch (\ValueError $e) {
            return \App\Enums\WhatsAppSetupState::NOT_CONFIGURED;
        }
    }

    /**
     * Check if setup is in a specific state
     */
    public function isInSetupState(string|\App\Enums\WhatsAppSetupState|\App\Enums\IntegrationState $state): bool
    {
        if ($state instanceof \App\Enums\IntegrationState) {
            return $this->whatsapp_setup_state === $state;
        }

        if (is_string($state)) {
            try {
                $state = \App\Enums\WhatsAppSetupState::from(strtoupper($state));
            } catch (\ValueError $e) {
                return false;
            }
        }

        return $this->getSetupState() === $state;
    }

    /**
     * Check if setup is active
     */
    public function isWhatsAppActive(): bool
    {
        return $this->isInSetupState(\App\Enums\WhatsAppSetupState::ACTIVE);
    }

    /**
     * Check if setup is degraded
     */
    public function isWhatsAppDegraded(): bool
    {
        return $this->isInSetupState(\App\Enums\WhatsAppSetupState::DEGRADED);
    }

    /**
     * Check if setup is suspended
     */
    public function isWhatsAppSuspended(): bool
    {
        return $this->isInSetupState(\App\Enums\WhatsAppSetupState::SUSPENDED);
    }

    /**
     * Check if the team still has persisted WhatsApp linkage data.
     */
    public function hasStoredWhatsAppConnection(): bool
    {
        return ! empty($this->whatsapp_access_token) && ! empty($this->whatsapp_business_account_id);
    }

    /**
     * Check if the team is currently in a subscription grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->subscription_grace_ends_at && $this->subscription_grace_ends_at->isFuture();
    }

    /**
     * Check if WhatsApp can send messages
     */
    public function canSendWhatsAppMessages(): bool
    {
        return in_array($this->getSetupState(), [
            \App\Enums\WhatsAppSetupState::ACTIVE,
            \App\Enums\WhatsAppSetupState::DEGRADED,
        ]);
    }

    /**
     * Get call routing configuration with defaults.
     */
    public function getCallRoutingConfig(): array
    {
        return array_merge([
            'mode' => 'round_robin',
            'role' => 'agent',
            'cooldown_seconds' => 60,
            'ring_timeout_seconds' => 30,
            'fallback_action' => 'auto_reply',
        ], $this->call_routing_config ?? []);
    }

    /**
     * Calculate a lead score (0-100) based on integration progress.
     */
    public function calculateLeadScore(): int
    {
        $score = 0;

        if ($this->whatsapp_access_token) {
            $score += 20;
        } // Connected FB
        if ($this->whatsapp_business_account_id) {
            $score += 20;
        } // WABA Found
        if ($this->whatsapp_phone_number_id) {
            $score += 30;
        } // Phone Registered
        if ($this->last_webhook_received_at) {
            $score += 30;
        } // Pulse Received (Active)

        return min(100, $score);
    }

    public function crmTasks()
    {
        return $this->morphMany(CrmTask::class, 'related_to');
    }

    public function crmActivities()
    {
        return $this->morphMany(CrmActivity::class, 'related_to');
    }

    /**
     * Get max call minutes per month from the plan if not explicitly set on the team.
     */
    public function getMaxCallMinutesPerMonthAttribute($value): int
    {
        if ($value !== null) {
            return (int) $value;
        }

        return (int) $this->getPlanLimit('max_call_minutes_per_month', 0);
    }
}

