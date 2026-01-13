<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'closed_at' => 'datetime',
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

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function notes()
    {
        return $this->hasMany(InternalNote::class)->orderBy('created_at', 'desc');
    }
}
