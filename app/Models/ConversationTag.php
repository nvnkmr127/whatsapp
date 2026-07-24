<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationTag extends Model
{
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'name',
        'color_code',
    ];

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_tag_pivot');
    }
}
