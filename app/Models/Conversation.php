<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, HasTeam, SoftDeletes;

    protected $fillable = [
        'team_id', 'contact_id', 'assigned_to', 'status',
        'last_message_at', 'sla_due_at', 'closed_at', 'close_reason',
        'first_response_at', 'sla_first_response_due_at', 'sla_resolution_due_at',
        'sla_status', 'sla_policy_id', 'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'closed_at' => 'datetime',
        'first_response_at' => 'datetime',
        'sla_first_response_due_at' => 'datetime',
        'sla_resolution_due_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function slaPolicy()
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function notes()
    {
        return $this->hasMany(InternalNote::class)->orderBy('created_at', 'desc');
    }

    public function calls()
    {
        return $this->hasMany(WhatsAppCall::class);
    }

    public function lastInboundMessage()
    {
        return $this->hasOne(Message::class)
            ->where('direction', 'inbound')
            ->latestOfMany();
    }

    public function getHasActiveCallAttribute()
    {
        return $this->calls()->whereIn('status', ['initiated', 'ringing', 'in_progress'])->exists();
    }

    /**
     * Check if the conversation is within the Meta 24-hour service window.
     *
     * Uses the `lastInboundMessage` eager-loaded relation when available to
     * avoid an extra query per conversation in list views.
     */
    public function isWithin24Hours(): bool
    {
        if (! $this->last_message_at) {
            return false;
        }

        $lastInbound = $this->relationLoaded('lastInboundMessage')
            ? $this->lastInboundMessage
            : $this->messages()->where('direction', 'inbound')->latest('id')->first();

        if (! $lastInbound) {
            return false;
        }

        return $lastInbound->created_at->addHours(24)->isFuture();
    }
}
