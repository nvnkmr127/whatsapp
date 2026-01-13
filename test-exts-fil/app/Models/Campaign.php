<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $rel_type
 * @property string|null                     $template_id
 * @property \Illuminate\Support\Carbon|null $scheduled_send_time
 * @property bool                            $send_now
 * @property array<array-key, mixed>|null    $header_params
 * @property array<array-key, mixed>|null    $body_params
 * @property array<array-key, mixed>|null    $footer_params
 * @property bool                            $pause_campaign
 * @property bool                            $select_all
 * @property bool                            $is_sent
 * @property int|null                        $sending_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null                     $filename
 * @property array<array-key, mixed>|null    $rel_data
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CampaignDetail> $details
 * @property-read int|null $details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CampaignDetail> $failedMessages
 * @property-read int|null $failed_messages_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign retryable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereBodyParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereFooterParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereHeaderParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereIsSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign wherePauseCampaign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRelData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereRelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereScheduledSendTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSelectAll($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSendNow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereSendingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Campaign whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rel_type',
        'template_id',
        'scheduled_send_time',
        'send_now',
        'header_params',
        'body_params',
        'footer_params',
        'pause_campaign',
        'select_all',
        'is_sent',
        'sending_count',
        'filename',
        'rel_data',
    ];

    protected $casts = [
        'scheduled_send_time' => 'datetime',
        'header_params'       => 'array',
        'body_params'         => 'array',
        'footer_params'       => 'array',
        'rel_data'            => 'array',
        'send_now'            => 'boolean',
        'pause_campaign'      => 'boolean',
        'select_all'          => 'boolean',
        'is_sent'             => 'boolean',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(CampaignDetail::class);
    }

    public function failedMessages(): HasMany
    {
        return $this->details()
            ->where('status', 0)
            ->whereNotNull('response_message');
    }

    public function scopePending($query)
    {
        return $query->where('is_sent', false)
            ->where('pause_campaign', false)
            ->where(function ($q) {
                $q->where('send_now', true)
                    ->orWhere('scheduled_send_time', '<=', now());
            });
    }

    public function scopeRetryable($query)
    {
        return $query->where('is_sent', true)
            ->where('pause_campaign', false)
            ->whereHas('failedMessages');
    }
}
