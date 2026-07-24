<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 */
class Contact extends Model
{
    use \App\Traits\HasTeam;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected static function booted()
    {
        static::updating(function ($contact) {
            $contact->version = ($contact->version ?? 0) + 1;
        });

        static::created(function ($contact) {
            \App\Events\ContactCreated::dispatch($contact);
        });

        static::saving(function ($contact) {
            if ($contact->isDirty('custom_attributes')) {
                $customAttributes = $contact->custom_attributes;
                if (is_array($customAttributes)) {
                    // Extract email if set
                    if (array_key_exists('email', $customAttributes)) {
                        $contact->email = !empty($customAttributes['email']) ? $customAttributes['email'] : null;
                        unset($customAttributes['email']);
                    }
                    
                    // Extract company if set
                    if (array_key_exists('company', $customAttributes)) {
                        $companyName = trim($customAttributes['company'] ?? '');
                        if (!empty($companyName)) {
                            // Find or create company under this team
                            $company = \App\Models\Company::firstOrCreate([
                                'team_id' => $contact->team_id,
                                'name' => $companyName,
                            ]);
                            $contact->company_id = $company->id;
                        } else {
                            $contact->company_id = null;
                        }
                        unset($customAttributes['company']);
                    }
                    
                    $contact->custom_attributes = $customAttributes;
                }
            }
        });
    }

    protected $fillable = [
        'team_id', 'name', 'phone_number', 'email', 'notes', 'custom_attributes', 'crm_source_id',
        'opt_in_status', 'opt_in_at', 'opt_in_expires_at', 'opt_in_source',
        'last_interaction_at', 'last_customer_message_at', 'last_seen_at',
        'is_bot_paused', 'bot_paused_at', 'bot_paused_until', 'has_pending_reply',
        'assigned_to', 'category_id', 'version',
        'avatar_url', 'company_id', 'notes_count',
        'bot_paused_reason', 'last_seen_agent_id', 'job_title', 'engagement_score',
        'is_within_24h_window', 'lifecycle_state',
        'message_count', 'inbound_message_count', 'outbound_message_count', 'conversation_count',
        'lead_source_id', 'avg_response_time', 'days_since_last_message', 'consent_age_days', 'is_consent_expired',
    ];

    protected $attributes = [
        'opt_in_status' => 'opted_in',
    ];

    protected $casts = [
        'custom_attributes' => 'array',
        'last_interaction_at' => 'datetime',
        'opt_in_at' => 'datetime',
        'opt_in_expires_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'is_bot_paused' => 'boolean',
        'bot_paused_at' => 'datetime',
        'bot_paused_until' => 'datetime',
        'has_pending_reply' => 'boolean',
    ];

    protected $appends = ['company'];

    /**
     * Check if contact has valid consent for marketing.
     */
    public function hasValidConsent(): bool
    {
        if ($this->opt_in_status !== 'opted_in') {
            return false;
        }

        if ($this->opt_in_expires_at && $this->opt_in_expires_at < now()) {
            return false;
        }

        return true;
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function tags()
    {
        return $this->belongsToMany(ContactTag::class, 'contact_tag_pivot', 'contact_id', 'tag_id')
            ->using(ContactTagPivot::class);
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @deprecated Use assignedToUser instead.
     */
    public function assignedTo()
    {
        return $this->assignedToUser();
    }

    public function notes()
    {
        return $this->hasMany(Note::class)->latest();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function activeConversation()
    {
        return $this->hasOne(Conversation::class)
            ->whereIn('status', ['new', 'open', 'waiting_reply'])
            ->latest();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attributedMessages()
    {
        return $this->hasMany(Message::class)->whereNotNull('attributed_campaign_id');
    }

    public function contactEvents()
    {
        return $this->hasMany(ContactEvent::class);
    }

    public function crmActivities()
    {
        return $this->morphMany(CrmActivity::class, 'related_to');
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function workflowLogs()
    {
        return $this->morphMany(WorkflowLog::class, 'subject');
    }

    public function automationRuns()
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function latestOutboundMessage()
    {
        return $this->hasOne(Message::class)->ofMany([
            'id' => 'max'
        ], function ($query) {
            $query->where('direction', 'outbound');
        });
    }

    public function companyRelation()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function getCompanyAttribute()
    {
        if (! $this->relationLoaded('companyRelation')) {
            return null;
        }
        return $this->companyRelation?->name;
    }
}
