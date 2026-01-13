<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations'; // Explicitly define table name

    protected $guarded = [];

    protected $casts = [
        'window_starts_at' => 'datetime',
        'window_ends_at' => 'datetime',
    ];
}
