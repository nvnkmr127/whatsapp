<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $receiver_id
 * @property string|null                     $last_message
 * @property string|null                     $last_msg_time
 * @property string|null                     $wa_no
 * @property string|null                     $wa_no_id
 * @property string                          $time_sent
 * @property string|null                     $type
 * @property string|null                     $type_id
 * @property string|null                     $agent
 * @property int                             $is_ai_chat
 * @property string|null                     $ai_message_json
 * @property int|null                        $is_bots_stoped
 * @property string|null                     $bot_stoped_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $messages
 * @property-read int|null $messages_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereAiMessageJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereBotStopedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereIsAiChat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereIsBotsStoped($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereLastMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereLastMsgTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereTimeSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereWaNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Chat whereWaNoId($value)
 *
 * @mixin \Eloquent
 */
class Chat extends Model
{
    use HasFactory;

    protected $table = 'chat';

    protected $fillable = [
        'name',
        'receiver_id',
        'last_message',
        'last_msg_time',
        'wa_no',
        'wa_no_id',
        'time_sent',
        'type',
        'type_id',
        'agent',
        'is_ai_chat',
        'ai_message_json',
        'is_bots_stoped',
        'bot_stoped_time',
    ];

    public $timestamps = true;

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'interaction_id');
    }
}
