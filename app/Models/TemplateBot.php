<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateBot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rel_type',
        'template_id',
        'header_params',
        'body_params',
        'footer_params',
        'filename',
        'trigger',
        'reply_type',
        'is_bot_active',
        'sending_count'
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
        return $this->belongsTo(WhatsappTemplate::class, 'template_id', 'template_id');
    }
}
