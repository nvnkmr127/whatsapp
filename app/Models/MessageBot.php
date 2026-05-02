<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBot extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \App\Traits\HasTeam;

    protected $table = 'message_bots';

    protected $fillable = [
        'team_id',
        'original_bot_id',
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
        'last_published_at',
        'last_modified_by',
        'sending_count',
        'daily_sending_count',
        'filename',
    ];

    protected $casts = [
        'trigger' => 'array',
        'is_bot_active' => 'boolean',
        'last_published_at' => 'datetime',
    ];

    /**
     * Get the source bot this was cloned from.
     */
    public function originalBot()
    {
        return $this->belongsTo(MessageBot::class, 'original_bot_id');
    }

    /**
     * Get the user who last modified this bot.
     */
    public function lastModifiedBy()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Scope a query to only include active bots.
     */
    public function scopeActive($query)
    {
        return $query->where('is_bot_active', true);
    }
}
