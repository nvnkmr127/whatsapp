<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateBot extends Model
{
    use \App\Traits\HasTeam;
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'rel_type',
        'template_id',
        'whatsapp_template_id',
        'header_params',
        'body_params',
        'footer_params',
        'filename',
        'trigger',
        'reply_type',
        'addedfrom',
        'is_bot_active',
        'sending_count',
    ];

    protected $casts = [
        'header_params' => 'array',
        'body_params' => 'array',
        'footer_params' => 'array',
        'trigger' => 'array',
        'is_bot_active' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id', 'whatsapp_template_id');
    }
}
