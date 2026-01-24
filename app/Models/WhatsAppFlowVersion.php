<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppFlowVersion extends Model
{
    protected $table = 'whatsapp_flow_versions';
    protected $guarded = [];

    protected $casts = [
        'design_data' => 'array',
        'flow_json' => 'array',
        'entry_point_config' => 'array',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlow::class, 'whatsapp_flow_id');
    }
}
