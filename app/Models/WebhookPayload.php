<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Model;

class WebhookPayload extends Model
{
    use HasTeam;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'mapped_data' => 'array',
        'next_retry_at' => 'datetime',
    ];

    public function source()
    {
        return $this->belongsTo(WebhookSource::class, 'webhook_source_id');
    }
}
