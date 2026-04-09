<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookWorkflow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }
}
