<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'window_starts_at' => 'datetime',
        'window_ends_at' => 'datetime',
    ];
}
