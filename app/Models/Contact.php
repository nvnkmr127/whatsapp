<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \App\Traits\HasTeam;

    protected static function booted()
    {
        static::updating(function ($contact) {
            $contact->version = ($contact->version ?? 0) + 1;
        });

        static::created(function ($contact) {
            try {
                if (class_exists(\App\Services\WorkflowEngine::class)) {
                    app(\App\Services\WorkflowEngine::class)->trigger('contact_created', $contact, ['source' => 'system']);
                }
            } catch (\Exception $e) {
                // Fail silently to not disrupt standard UI logic
                \Illuminate\Support\Facades\Log::error("Workflow trigger failed on Contact Creation: " . $e->getMessage());
            }
        });
    }

    protected $guarded = [];

    protected $attributes = [
        'opt_in_status' => 'opted_in',
    ];

    protected $casts = [
        'custom_attributes' => 'array',
        'last_interaction_at' => 'datetime',
        'opt_in_at' => 'datetime',
        'opt_in_expires_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'sla_breached_at' => 'datetime',
        'has_pending_reply' => 'boolean',
    ];

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
        return $this->belongsToMany(ContactTag::class, 'contact_tag_pivot', 'contact_id', 'tag_id');
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
}
