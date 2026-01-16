<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $guarded = [];

    protected $casts = [
        'custom_attributes' => 'array',
        'last_interaction_at' => 'datetime',
        'opt_in_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'sla_breached_at' => 'datetime',
        'has_pending_reply' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function tags()
    {
        return $this->belongsToMany(ContactTag::class, 'contact_tag_pivot', 'contact_id', 'tag_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->hasMany(Note::class)->latest();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function activeConversation()
    {
        return $this->hasOne(Conversation::class)
            ->whereIn('status', ['new', 'open', 'waiting_reply'])
            ->latest();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
