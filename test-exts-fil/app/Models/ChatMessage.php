<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property int                             $interaction_id
 * @property string                          $sender_id
 * @property string|null                     $url
 * @property string                          $message
 * @property string|null                     $status
 * @property string                          $time_sent
 * @property string|null                     $message_id
 * @property string|null                     $staff_id
 * @property string|null                     $type
 * @property int                             $is_read
 * @property string|null                     $ref_message_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Chat|null $chat
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereInteractionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereRefMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereStaffId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereTimeSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereUrl($value)
 *
 * @property string|null $status_message
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereStatusMessage($value)
 *
 * @mixin \Eloquent
 */
class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'interaction_id',
        'sender_id',
        'url',
        'message',
        'status',
        'time_sent',
        'message_id',
        'staff_id',
        'type',
        'is_read',
        'ref_message_id',
        'status_message',
    ];

    public $timestamps = true;

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'interaction_id');
    }
}
