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

    public function getTimeline($excludeSuperAdmin = true)
    {
        $timeline = collect();

        // 1. Messages
        $this->messages()->latest()->take(50)->get()->each(function ($message) use ($timeline) {
            $timeline->push([
                'type' => 'message',
                'id' => 'msg-' . $message->id,
                'title' => $message->direction === 'inbound' ? 'Message Received' : 'Message Sent',
                'description' => $message->content,
                'occurred_at' => $message->sent_at ?? $message->created_at,
                'metadata' => [
                    'direction' => $message->direction,
                    'status' => $message->status,
                    'type' => $message->type,
                ]
            ]);
        });

        // 2. Notes
        $this->notes()->with('user')->get()->each(function ($note) use ($timeline, $excludeSuperAdmin) {
            if ($excludeSuperAdmin && $note->user?->is_super_admin) {
                return;
            }
            $timeline->push([
                'type' => 'note',
                'id' => 'note-' . $note->id,
                'title' => 'Note Added',
                'description' => $note->content,
                'occurred_at' => $note->created_at,
                'user' => $note->user?->name,
            ]);
        });

        // 3. Contact Events
        $this->contactEvents()->get()->each(function ($event) use ($timeline) {
            $timeline->push([
                'type' => 'event',
                'id' => 'event-' . $event->id,
                'title' => str_replace('_', ' ', ucfirst($event->event_type)),
                'description' => $event->event_data['description'] ?? '',
                'occurred_at' => $event->occurred_at ?? $event->created_at,
                'metadata' => $event->event_data,
            ]);
        });

        // 4. CRM Activities
        $this->crmActivities()->with('user')->get()->each(function ($activity) use ($timeline, $excludeSuperAdmin) {
            if ($excludeSuperAdmin && $activity->user?->is_super_admin) {
                return;
            }
            $timeline->push([
                'type' => 'crm_activity',
                'id' => 'crm-' . $activity->id,
                'title' => str_replace('_', ' ', ucfirst($activity->activity_type)),
                'description' => $activity->description,
                'occurred_at' => $activity->performed_at ?? $activity->created_at,
                'user' => $activity->user?->name,
                'metadata' => $activity->metadata,
            ]);
        });

        // 5. Orders
        $this->orders()->get()->each(function ($order) use ($timeline) {
            $timeline->push([
                'type' => 'order',
                'id' => 'order-' . $order->id,
                'title' => 'Order Placed: #' . ($order->order_id ?? $order->id),
                'description' => "Amount: {$order->total_amount} {$order->currency} - Status: " . ucfirst($order->status),
                'occurred_at' => $order->created_at,
                'metadata' => $order->toArray(),
            ]);
        });

        // 6. Deals
        $this->deals()->get()->each(function ($deal) use ($timeline) {
            $timeline->push([
                'type' => 'deal',
                'id' => 'deal-' . $deal->id,
                'title' => 'Deal Created: ' . $deal->title,
                'description' => "Value: {$deal->value} {$deal->currency} - Status: " . ucfirst($deal->status),
                'occurred_at' => $deal->created_at,
                'metadata' => $deal->toArray(),
            ]);
        });

        // 7. Workflow Logs
        $this->workflowLogs()->with('workflow')->get()->each(function ($log) use ($timeline) {
            $timeline->push([
                'type' => 'automation',
                'id' => 'auto-' . $log->id,
                'title' => 'Automation Triggered: ' . ($log->workflow->name ?? 'Workflow'),
                'description' => "Status: " . ucfirst($log->status),
                'occurred_at' => $log->started_at ?? $log->created_at,
                'metadata' => $log->execution_data,
            ]);
        });

        // 8. Activity Logs
        $this->activityLogs()->with('user')->get()->each(function ($log) use ($timeline, $excludeSuperAdmin) {
            if ($excludeSuperAdmin && $log->user?->is_super_admin) {
                return;
            }
            $timeline->push([
                'type' => 'activity_log',
                'id' => 'act-' . $log->id,
                'title' => 'Action Logged',
                'description' => $log->description ?? 'System activity occurred',
                'occurred_at' => $log->created_at,
                'user' => $log->user?->name,
                'metadata' => $log->properties,
            ]);
        });

        return $timeline->sortByDesc('occurred_at')->values();
    }

    public function getMediaVault()
    {
        return $this->messages()
            ->whereNotNull('media_url')
            ->orderByDesc('created_at')
            ->get(['id', 'type', 'media_url', 'media_type', 'caption', 'created_at']);
    }

    public function getInteractionHeatmap()
    {
        $interactions = $this->messages()
            ->selectRaw('DAYOFWEEK(created_at) as day, HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('day', 'hour')
            ->get();

        $heatmap = [];
        // Initialize with zeros for a 7x24 grid
        for ($d = 1; $d <= 7; $d++) {
            for ($h = 0; $h < 24; $h++) {
                $heatmap[$d][$h] = 0;
            }
        }

        foreach ($interactions as $interaction) {
            $heatmap[$interaction->day][$interaction->hour] = $interaction->count;
        }

        return $heatmap;
    }
}
