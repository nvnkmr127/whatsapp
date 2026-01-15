<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppFlowResponse extends Model
{
    protected $table = 'whatsapp_flow_responses';
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function whatsappFlow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
