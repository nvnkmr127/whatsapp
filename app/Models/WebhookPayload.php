<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookPayload extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'mapped_data' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(WebhookSource::class, 'webhook_source_id');
    }
}
