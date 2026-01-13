<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBot extends Model
{
    protected $table = 'message_bots';

    protected $fillable = [
        'name',
        'rel_type',
        'reply_text',
        'reply_type',
        'trigger',
        'bot_header',
        'bot_footer',
        'button1',
        'button1_id',
        'button2',
        'button2_id',
        'button3',
        'button3_id',
        'button_name',
        'button_url',
        'addedfrom',
        'is_bot_active',
        'sending_count',
        'filename',
    ];

    protected $casts = [
        'trigger' => 'array',
        'is_bot_active' => 'boolean',
    ];
}
