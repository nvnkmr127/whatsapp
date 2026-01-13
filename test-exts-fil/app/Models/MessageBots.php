<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $rel_type
 * @property string                          $reply_text
 * @property int                             $reply_type
 * @property array<array-key, mixed>|null    $trigger
 * @property string|null                     $bot_header
 * @property string|null                     $bot_footer
 * @property string|null                     $button1
 * @property string|null                     $button1_id
 * @property string|null                     $button2
 * @property string|null                     $button2_id
 * @property string|null                     $button3
 * @property string|null                     $button3_id
 * @property string|null                     $button_name
 * @property string|null                     $button_url
 * @property int                             $addedfrom
 * @property int                             $is_bot_active
 * @property int                             $sending_count
 * @property string|null                     $filename
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereAddedfrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereBotFooter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereBotHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButton1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButton1Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButton2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButton2Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButton3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButton3Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButtonName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereButtonUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereIsBotActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereRelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereReplyText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereReplyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereSendingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereTrigger($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MessageBots whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class MessageBots extends Model
{
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
        'used',
        'is_bot_active',
        'sending_count',
        'filename',
    ];

    protected $casts = [
        'trigger' => 'array',
    ];

    public static function getMessageBotsByRelType($relType, $message, $replyType = null)
    {
        $query = self::where('rel_type', $relType)
            ->where('is_bot_active', 1);

        if (! is_null($replyType)) {
            $query->where('reply_type', $replyType);
        }

        if (! empty($message) && $replyType != 4) {
            $messageWords = explode(' ', $message);
            $query->where(function ($q) use ($messageWords) {
                foreach ($messageWords as $word) {
                    $cleanWord = str_replace(["'", '"'], '', $word); // Remove extra quotes
                    $q->orWhereRaw("FIND_IN_SET(?, TRIM(BOTH '\"' FROM `trigger`)) > 0", [$cleanWord]);
                }
            });
        }

        $data = $query->get()->toArray();

        return $data;
    }
}
