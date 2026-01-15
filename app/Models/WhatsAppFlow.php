<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppFlow extends Model
{
    protected $table = 'whatsapp_flows';
    protected $guarded = [];

    public $fillable = ['team_id', 'flow_id', 'name', 'category', 'status', 'design_data', 'flow_json', 'uses_data_endpoint'];

    protected $casts = [
        'design_data' => 'array',
        'flow_json' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(WhatsAppFlowResponse::class);
    }
}
