<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use \App\Traits\HasTeam;

class WebhookPayload extends Model
{
    use HasTeam;

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
