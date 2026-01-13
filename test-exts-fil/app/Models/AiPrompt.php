<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $action
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiPrompt whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AiPrompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'action',
        'is_public',
        'added_from',
    ];
}
