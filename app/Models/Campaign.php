<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use \App\Traits\HasTeam;
    use SoftDeletes;

    protected $fillable = [
        'team_id', 'user_id', 'name', 'campaign_name', 'status', 'campaign_type',
        'template_id', 'whatsapp_template_id', 'template_name', 'template_language',
        'template_variables', 'header_params', 'body_params', 'footer_params',
        'audience_filters', 'total_contacts', 'sent_count', 'del_count', 'read_count',
        'scheduled_at', 'started_at', 'completed_at',
        'retry_config', 'is_ab_test', 'split_ratio', 'send_rate', 'steps',
        'error_message', 'last_snapshot_id', 'snapshot_id',
        'variant', 'drip_tracking', 'drip_trigger_type', 'drip_send_to_existing',
    ];

    protected $casts = [
        'template_variables' => 'array',
        'audience_filters' => 'array',
        'header_params' => 'array',
        'body_params' => 'array',
        'footer_params' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'retry_config' => 'array',
        'total_contacts' => 'integer',
        'sent_count' => 'integer',
        'del_count' => 'integer',
        'read_count' => 'integer',
        'is_ab_test' => 'boolean',
        'split_ratio' => 'integer',
        'send_rate' => 'integer',
        'steps' => 'array',
        'drip_send_to_existing' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($campaign) {
            // Fix field name mapping
            if (empty($campaign->name) && ! empty($campaign->campaign_name)) {
                $campaign->name = $campaign->campaign_name;
            }
            if (empty($campaign->campaign_name) && ! empty($campaign->name)) {
                $campaign->campaign_name = $campaign->name;
            }

            // --- CAMPAIGN LIFECYCLE GUARDS (UC-SAFE-08) ---
            if (! $campaign->isDirty('status')) {
                return;
            }

            $oldStatus = $campaign->getOriginal('status');
            $newStatus = $campaign->status;

            // Terminal status protection
            if (in_array($oldStatus, ['completed', 'failed']) && $newStatus !== $oldStatus) {
                throw new \Exception("Cannot transition out of terminal campaign state: {$oldStatus}.");
            }

            // Progress preservation
            if (in_array($oldStatus, ['sending', 'processing', 'paused', 'active']) && in_array($newStatus, ['scheduled', 'draft'])) {
                throw new \Exception("Cannot revert a campaign to {$newStatus} once sending has begun.");
            }
        });
    }

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastSnapshot()
    {
        return $this->belongsTo(CampaignSnapshot::class, 'last_snapshot_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusStyleAttribute()
    {
        return match ($this->status) {
            'completed', 'sent' => 'bg-wa-green/10 text-wa-green border-wa-green/20',
            'processing', 'sending' => 'bg-wa-teal/10 text-wa-teal border-wa-teal/20',
            'active' => 'bg-wa-teal/10 text-wa-teal border-wa-teal/20 animate-pulse',
            'paused' => 'bg-wa-orange/10 text-wa-orange border-wa-orange/20',
            'scheduled' => 'bg-wa-orange/10 text-wa-orange border-wa-orange/20',
            'failed' => 'bg-rose-50 text-rose-600 border-rose-100',
            default => 'bg-slate-50 text-slate-500 border-slate-200',
        };
    }

    public function getDeliveryPercentageAttribute()
    {
        if (($this->total_contacts ?? 0) <= 0) {
            return 0;
        }

        return ($this->sent_count / $this->total_contacts) * 100;
    }

    public function getReadPercentageAttribute()
    {
        if (($this->sent_count ?? 0) <= 0) {
            return 0;
        }

        return ($this->read_count / $this->sent_count) * 100;
    }

    /**
     * Create a clone of the campaign with reset metrics and status.
     *
     * @return Campaign
     */
    public function replicateAndReset()
    {
        $clone = $this->replicate([
            'status',
            'total_contacts',
            'sent_count',
            'del_count',
            'read_count',
            'scheduled_at',
            'started_at',
            'completed_at',
            'last_snapshot_id',
            'batch_id',
            'error_message',
            'error_notified_at',
        ]);

        $clone->status = 'draft';
        $clone->total_contacts = 0;
        $clone->sent_count = 0;
        $clone->del_count = 0;
        $clone->read_count = 0;
        $clone->scheduled_at = null;
        $clone->started_at = null;
        $clone->completed_at = null;
        $clone->last_snapshot_id = null;
        $clone->batch_id = null;
        $clone->error_message = null;
        $clone->error_notified_at = null;

        $cloneName = ($this->campaign_name ?? $this->name).' (Clone)';
        $clone->campaign_name = $cloneName;
        $clone->name = $cloneName;

        return $clone;
    }
}
