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
        'reply_to_message_id', 'agent_id', 'user_id',
        'whatsapp_message_id', 'external_id',
        'direction', 'type', 'content', 'metadata', 'status',
        'media_id', 'media_url', 'media_type', 'caption',
        'error_message', 'last_error', 'retry_count', 'next_retry_at',
        'is_starred', 'sent_at', 'delivered_at', 'read_at', 'updated_at', 'to',
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
        if (!array_key_exists('created_at', $this->attributes) || !$this->attributes['created_at']) {
            return null;
        }
        return $this->created_at ? $this->created_at->format('H:i') : null;
    }

    public function getReplyToMessageIdAttribute($value)
    {
        if ($value) {
            return $value;
        }
        if (!array_key_exists('metadata', $this->attributes)) {
            return null;
        }
        $meta = $this->attributes['metadata'] ?? null;
        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }
        return is_array($meta) ? ($meta['reply_to_message_id'] ?? null) : null;
    }

    public function getFullMediaUrlAttribute()
    {
        if (!array_key_exists('media_url', $this->attributes) || !$this->attributes['media_url']) {
            return null;
        }

        $url = trim($this->attributes['media_url']);
        if (!$url) {
            return null;
        }

        if (str_starts_with($url, 'data:') || str_starts_with($url, 'file://')) {
            return $url;
        }

        if (preg_match('#^https?://[^/]+/(storage|public)/(.*)$#i', $url, $matches)) {
            $cleanPath = $matches[2];
        } elseif (preg_match('#^https?://(localhost|127\.0\.0\.1|10\.0\.2\.2)(:\d+)?/(.*)$#i', $url, $matches)) {
            $cleanPath = preg_replace('#^/?(storage|public)/#i', '', $matches[3]);
        } elseif (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        } else {
            $cleanPath = preg_replace('#^/?(storage|public)/#i', '', $url);
        }

        $configuredDisk = config('filesystems.default', 'public');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;

        try {
            return \Illuminate\Support\Facades\Storage::disk($disk)->url($cleanPath);
        } catch (\Throwable $e) {
            return asset('storage/' . ltrim($cleanPath, '/'));
        }
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
