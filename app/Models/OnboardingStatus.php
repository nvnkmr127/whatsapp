<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingStatus extends Model
{
    protected $fillable = [
        'user_id',
        'account_created',
        'whatsapp_connected',
        'business_profile_completed',
        'first_template_created',
        'first_campaign_created',
        'ai_training_completed',
        'onboarding_completed',
        'last_activity_at',
        'reminders_sent_count',
        'was_recovered',
        'recovered_at',
    ];

    protected $casts = [
        'account_created' => 'boolean',
        'whatsapp_connected' => 'boolean',
        'business_profile_completed' => 'boolean',
        'first_template_created' => 'boolean',
        'first_campaign_created' => 'boolean',
        'ai_training_completed' => 'boolean',
        'onboarding_completed' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
