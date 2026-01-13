<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $name
 * @property string                          $subject
 * @property string                          $slug
 * @property string                          $merge_fields_groups
 * @property string                          $message
 * @property int                             $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereMergeFieldsGroups($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailTemplate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'message',
        'merge_fields_groups',
        'is_active',
    ];

    protected $casts = [
        'merge_fields_groups' => 'array',
        'is_active'           => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(
            function ($model) {
                if ($model->name) {
                    // Check if the model is being created (no existing ID means new record)
                    if (! $model->exists) {
                        $slug = \Str::slug($model->name);

                        // Check if the slug already exists in the database
                        $existingSlug = self::where('slug', $slug)->exists();

                        if ($existingSlug) {
                            $slug .= '-' . time();
                        }
                        $model->slug = $slug;
                    } else {
                        // For updates, ensure slug remains unchanged
                        if (! $model->isDirty('slug')) {
                            $model->slug = $model->getOriginal('slug');
                        }
                    }
                }
            }
        );
    }
}
