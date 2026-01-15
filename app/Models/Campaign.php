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
}
