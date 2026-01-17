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
}
