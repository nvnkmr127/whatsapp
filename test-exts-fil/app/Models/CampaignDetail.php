<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $campaign_id
 * @property int|null                        $rel_id
 * @property string                          $rel_type
 * @property string|null                     $header_message
 * @property string|null                     $body_message
 * @property string|null                     $footer_message
 * @property int|null                        $status
 * @property string|null                     $response_message
 * @property string|null                     $whatsapp_id
 * @property string|null                     $message_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Campaign $campaign
 * @property-read \App\Models\Contact|null $contact
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereBodyMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereFooterMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereHeaderMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereMessageStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereRelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereRelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereResponseMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CampaignDetail whereWhatsappId($value)
 *
 * @mixin \Eloquent
 */
class CampaignDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'rel_id',
        'rel_type',
        'header_message',
        'body_message',
        'footer_message',
        'status',
        'response_message',
        'whatsapp_id',
        'message_status',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'rel_id');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 0)
            ->whereNotNull('response_message');
    }
}
