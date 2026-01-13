<?php

namespace App\Models;

use App\Traits\HasWebhooks;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $color
 * @property int                             $isdefault
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contact> $contacts
 * @property-read int|null $contacts_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereIsdefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Status whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Status extends Model
{
    use HasFactory;
    use HasWebhooks;

    protected $fillable = [
        'name',
        'color',
        'isdefault',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
