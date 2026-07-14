<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use \App\Traits\HasTeam;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'team_id', 'contact_id', 'conversation_id', 'campaign_id', 'attributed_campaign_id',
        'webhook_workflow_id', 'webhook_source_id', 'automation_id', 'automation_run_id',
        'reply_to_message_id', 'agent_id',
        'whatsapp_message_id', 'external_id',
        'direction', 'type', 'content', 'metadata', 'status',
        'media_id', 'media_url', 'media_type', 'caption',
        'error_message', 'last_error', 'retry_count', 'next_retry_at',
        'is_starred', 'sent_at', 'delivered_at', 'read_at', 'updated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'is_starred' => 'boolean',
    ];

    protected $appends = ['pretty_time', 'full_media_url'];

    public function getPrettyTimeAttribute()
    {
        return $this->created_at ? $this->created_at->format('H:i') : null;
    }

    public function getReplyToMessageIdAttribute()
    {
        return $this->metadata['reply_to_message_id'] ?? null;
    }

    public function getFullMediaUrlAttribute()
    {
        if (!$this->media_url) {
            return null;
        }

        if (str_starts_with($this->media_url, 'http')) {
            return $this->media_url;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->media_url);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function attributedCampaign()
    {
        return $this->belongsTo(Campaign::class, 'attributed_campaign_id');
    }

    public function webhookWorkflow()
    {
        return $this->belongsTo(WebhookWorkflow::class);
    }

    public function webhookSource()
    {
        return $this->belongsTo(WebhookSource::class);
    }

    public function automation()
    {
        return $this->belongsTo(Automation::class);
    }

    public function automationRun()
    {
        return $this->belongsTo(AutomationRun::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }
}
