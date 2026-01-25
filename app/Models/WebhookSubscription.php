<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if this subscription is subscribed to a specific event.
     */
    public function isSubscribedTo(string $event): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // If events is null or empty, subscribe to all events
        if (empty($this->events)) {
            return true;
        }

        return in_array($event, $this->events);
    }
}
