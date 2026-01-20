<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $guarded = [];

    protected $casts = [
        'template_variables' => 'array',
        'audience_filters' => 'array',
        'header_params' => 'array',
        'body_params' => 'array',
        'footer_params' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($campaign) {
            if (empty($campaign->name) && !empty($campaign->campaign_name)) {
                $campaign->name = $campaign->campaign_name;
            }
            if (empty($campaign->campaign_name) && !empty($campaign->name)) {
                $campaign->campaign_name = $campaign->name;
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
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
            'scheduled' => 'bg-wa-orange/10 text-wa-orange border-wa-orange/20',
            'failed' => 'bg-rose-50 text-rose-600 border-rose-100',
            default => 'bg-slate-50 text-slate-500 border-slate-200',
        };
    }

    public function getDeliveryPercentageAttribute()
    {
        if (($this->total_contacts ?? 0) <= 0)
            return 0;
        return ($this->sent_count / $this->total_contacts) * 100;
    }

    public function getReadPercentageAttribute()
    {
        if (($this->sent_count ?? 0) <= 0)
            return 0;
        return ($this->read_count / $this->sent_count) * 100;
    }
}
