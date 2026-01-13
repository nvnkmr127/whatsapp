<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $title
 * @property string                          $description
 * @property int                             $is_public
 * @property int                             $added_from
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereAddedFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CannedReply whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CannedReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_public',
        'added_from',
    ];
}
