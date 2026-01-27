<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \App\Traits\HasTeam;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

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
}
