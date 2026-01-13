<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property int                             $isread
 * @property int                             $isread_inline
 * @property string                          $date
 * @property string                          $description
 * @property int                             $fromuserid
 * @property int                             $fromclientid
 * @property string                          $from_fullname
 * @property int                             $touserid
 * @property int|null                        $fromcompany
 * @property string|null                     $link
 * @property string|null                     $additional_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereAdditionalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereFromFullname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereFromclientid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereFromcompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereFromuserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereIsread($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereIsreadInline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereTouserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PusherNotification whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PusherNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'isread',
        'isread_inline',
        'date',
        'description',
        'fromuserid',
        'fromclientid',
        'from_fullname',
        'touserid',
        'fromcompany',
        'link',
        'additional_data',
    ];
}
