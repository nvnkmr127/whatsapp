<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_subscription_id',
        'event_type',
        'payload',
        'status_code',
        'response',
        'attempted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempted_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(WebhookSubscription::class, 'webhook_subscription_id');
    }

    /**
     * Check if the delivery was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }
}
