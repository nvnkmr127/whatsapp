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
            ->withPivot('role', 'receives_tickets')
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
        'whatsapp_business_account_id',
        'whatsapp_access_token',
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
        'subscription_status',
        'whatsapp_messaging_limit',
        'whatsapp_quality_rating',
        'whatsapp_phone_display',
        'whatsapp_verified_name',
        'whatsapp_setup_state',
        'whatsapp_token_expires_at',
        'whatsapp_token_last_validated',
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
            'opt_in_keywords' => 'array',
            'opt_out_keywords' => 'array',
            'opt_in_message_enabled' => 'boolean',
            'opt_out_message_enabled' => 'boolean',
            'chat_assignment_config' => 'array',
            'chat_status_rules' => 'array',
            'commerce_config' => 'array',
            'subscription_ends_at' => 'datetime',
            'whatsapp_setup_progress' => 'array',
            'whatsapp_setup_started_at' => 'datetime',
            'whatsapp_setup_completed_at' => 'datetime',
            'whatsapp_setup_in_progress' => 'boolean',
            'whatsapp_setup_state' => \App\Enums\IntegrationState::class,
            'whatsapp_token_expires_at' => 'datetime',
            'whatsapp_token_last_validated' => 'datetime',
        ];
    }

    /**
     * Check if current time is within business hours
     */
    public function isWithinBusinessHours()
    {
        if (!$this->business_hours)
            return true; // Default open if not set

        $timezone = $this->timezone ?? 'UTC';
        $now = \Carbon\Carbon::now($timezone);
        $dayVal = strtolower($now->format('D')); // mon, tue, wed...

        $config = $this->business_hours[$dayVal] ?? null; // ['09:00', '17:00']

        if (!$config || !is_array($config) || count($config) !== 2) {
            // If config missing for day, assume Closed? Or Open? 
            // Let's assume Closed if key exists but null, Open if logic undefined.
            // Actually, usually if not defined, it's open or closed.
            // Let's assume: If business_hours is set, but key is missing -> Closed.
            // If business_hours is null -> Open 24/7.
            return false;
        }

        $start = \Carbon\Carbon::createFromTimeString($config[0], $timezone);
        $end = \Carbon\Carbon::createFromTimeString($config[1], $timezone);

        return $now->between($start, $end);
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

    /**
     * Centralized feature check logic.
     * Checks subscription plan and active add-ons.
     */
    public function hasFeature(string $feature): bool
    {
        // 1. Check Subscription Status
        if ($this->subscription_status === 'expired' || ($this->subscription_ends_at && $this->subscription_ends_at->isPast())) {
            return false;
        }

        // 2. Check Plan Features
        $plan = Plan::where('name', $this->subscription_plan ?? 'basic')->first();
        if ($plan && $plan->hasFeature($feature)) {
            return true;
        }

        // 3. Check Add-ons
        return $this->addOns()
            ->where('type', $feature)
            ->get()
            ->contains(fn($addon) => $addon->isActive());
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
     * Check if WhatsApp can send messages
     */
    public function canSendWhatsAppMessages(): bool
    {
        return in_array($this->getSetupState(), [
            \App\Enums\WhatsAppSetupState::ACTIVE,
            \App\Enums\WhatsAppSetupState::DEGRADED,
        ]);
    }
}
