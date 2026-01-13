<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int                             $id
 * @property string                          $event
 * @property string                          $model
 * @property string                          $url
 * @property string                          $status
 * @property int                             $attempt
 * @property array<array-key, mixed>         $payload
 * @property array<array-key, mixed>|null    $response
 * @property string|null                     $error_message
 * @property int|null                        $status_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $is_successful
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog successful()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereAttempt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereUrl($value)
 *
 * @property string|null $job_id
 * @property int|null    $entity_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookLog whereJobId($value)
 *
 * @mixin \Eloquent
 */
class WebhookLog extends Model
{
    protected $fillable = [
        'event',
        'model',
        'url',
        'status',
        'attempt',
        'payload',
        'response',
        'error_message',
        'status_code',
    ];

    protected $casts = [
        'payload'     => 'array',
        'response'    => 'array',
        'attempt'     => 'integer',
        'status_code' => 'integer',
    ];

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'success';
    }

    public static function cleanup(int $days = 30): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
