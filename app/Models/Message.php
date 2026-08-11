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

        if (str_starts_with($url, 'file://')) {
            return null;
        }

        if (str_starts_with($url, 'data:')) {
            return $url;
        }

        if (preg_match('#^https?://[^/]+/(storage|public)/(.*)$#i', $url, $matches)) {
            $cleanPath = $matches[2];
        } elseif (preg_match('#^https?://(localhost|127\.0\.0\.1|10\.0\.2\.2|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(:\d+)?/(.*)$#i', $url, $matches)) {
            $cleanPath = preg_replace('#^/?(storage|public)/#i', '', $matches[3]);
        } elseif (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            if (preg_match('#^https?://(localhost|127\.0\.0\.1|10\.0\.2\.2|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(:\d+)?/(.*)$#i', $url, $matches)) {
                $cleanPath = preg_replace('#^/?(storage|public)/#i', '', $matches[3]);
            } else {
                return $url;
            }
        } else {
            $cleanPath = preg_replace('#^/?(storage|public)/#i', '', $url);
        }

        $cleanPath = ltrim($cleanPath, '/');

        // Remote disks (R2/S3): the file lives in the bucket, not on this web
        // server's /storage/ path, so a local "{origin}/storage/..." URL 403s.
        // Sign a short-lived URL — works whether the bucket is public or private,
        // so customer media (which can include ID documents) stays non-public.
        $configuredDisk = config('filesystems.default', 'public');
        $remoteDisk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;
        if (config("filesystems.disks.{$remoteDisk}.driver") === 's3') {
            try {
                return \Illuminate\Support\Facades\Storage::disk($remoteDisk)->temporaryUrl($cleanPath, now()->addHours(6));
            } catch (\Throwable $e) {
                try {
                    return \Illuminate\Support\Facades\Storage::disk($remoteDisk)->url($cleanPath);
                } catch (\Throwable $e2) {
                    return null;
                }
            }
        }

        // Local public disk: serve via the app origin's /storage/ symlink.
        $origin = null;
        if (request() && request()->hasHeader('X-Forwarded-Host')) {
            $proto = request()->header('X-Forwarded-Proto', 'https');
            $host = request()->header('X-Forwarded-Host');
            $origin = "{$proto}://{$host}";
        } elseif (request() && request()->host() && !in_array(request()->host(), ['localhost', '127.0.0.1', '10.0.2.2'])) {
            $origin = request()->getSchemeAndHttpHost();
        }

        if (!$origin) {
            $envUrl = config('app.url');
            if ($envUrl && !str_contains($envUrl, 'localhost') && !str_contains($envUrl, '127.0.0.1')) {
                $origin = rtrim($envUrl, '/');
            }
        }

        if ($origin) {
            return "{$origin}/storage/{$cleanPath}";
        }

        $configuredDisk = config('filesystems.default', 'public');
        $disk = ($configuredDisk === 'local') ? 'public' : $configuredDisk;

        try {
            $storageUrl = \Illuminate\Support\Facades\Storage::disk($disk)->url($cleanPath);
            if (str_contains($storageUrl, 'localhost') || str_contains($storageUrl, '127.0.0.1')) {
                return asset('storage/' . $cleanPath);
            }
            return $storageUrl;
        } catch (\Throwable $e) {
            return asset('storage/' . $cleanPath);
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
