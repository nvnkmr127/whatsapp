<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $rel_type
 * @property int|null                        $template_id
 * @property array<array-key, mixed>|null    $header_params
 * @property array<array-key, mixed>|null    $body_params
 * @property array<array-key, mixed>|null    $footer_params
 * @property string|null                     $filename
 * @property array<array-key, mixed>|null    $trigger
 * @property int                             $reply_type
 * @property int                             $is_bot_active
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int                             $sending_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereBodyParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereFooterParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereHeaderParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereIsBotActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereRelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereReplyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereSendingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereTrigger($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TemplateBot whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TemplateBot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rel_type',
        'template_id',
        'header_params',
        'body_params',
        'footer_params',
        'filename',
        'trigger',
        'reply_type',
        'is_bot_active',
    ];

    protected $casts = [
        'header_params' => 'array',
        'body_params'   => 'array',
        'footer_params' => 'array',
        'trigger'       => 'array',
    ];

    public static function getTemplateBotsByRelType($relType, $message, $replyType = null)
    {
        // Start with the query on TemplateBot model
        $query = TemplateBot::join('whatsapp_templates', 'template_bots.template_id', '=', 'whatsapp_templates.template_id')
            ->select('template_bots.id AS template_bot_id', 'template_bots.*', 'whatsapp_templates.*')
            ->where('template_bots.rel_type', $relType)
            ->where('template_bots.is_bot_active', 1);

        // Optionally filter by reply_type
        if (! is_null($replyType)) {
            $query->where('reply_type', $replyType);
        }

        // If message is provided, search for each word in the trigger column
        if (! empty($message) && $replyType != 4) {
            $messageWords = explode(' ', $message);

            $query->where(function ($q) use ($messageWords) {
                foreach ($messageWords as $word) {
                    $cleanWord = str_replace(["'", '"'], '', $word);
                    $q->orWhereRaw("FIND_IN_SET(?, TRIM(BOTH '\"' FROM `trigger`)) > 0", [$cleanWord]);
                }
            });
        }

        $data = $query->get()->toArray();

        return $data;
    }
}
