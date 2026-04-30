<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsatRating extends Model
{
    protected $fillable = [
        'team_id', 'conversation_id', 'contact_id', 'agent_id',
        'rating', 'feedback', 'type', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'rating'   => 'integer',
    ];

    public function team(): BelongsTo       { return $this->belongsTo(Team::class); }
    public function conversation(): BelongsTo{ return $this->belongsTo(Conversation::class); }
    public function contact(): BelongsTo    { return $this->belongsTo(Contact::class); }
    public function agent(): BelongsTo      { return $this->belongsTo(User::class, 'agent_id'); }

    // Emoji label for UI display
    public function getRatingLabelAttribute(): string
    {
        return match ($this->rating) {
            1 => '😞 Terrible',
            2 => '😕 Bad',
            3 => '😐 Okay',
            4 => '😊 Good',
            5 => '😍 Excellent',
            default => 'N/A',
        };
    }
}
