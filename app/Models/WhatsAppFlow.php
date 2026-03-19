<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use \App\Traits\HasTeam;

class WhatsAppFlow extends Model
{
    use HasTeam;

    protected static function booted()
    {
        static::saving(function ($flow) {
            if (!$flow->isDirty('status')) {
                return;
            }

            $oldStatus = $flow->getOriginal('status');
            $newStatus = $flow->status;

            // Terminal status: DEPRECATED
            if ($oldStatus === 'DEPRECATED' && $newStatus !== 'DEPRECATED') {
                throw new \Exception("Cannot transition out of terminal state: DEPRECATED.");
            }

            // Safety: cannot move back to draft once published (risk of sync error with Meta)
            if ($oldStatus === 'PUBLISHED' && $newStatus === 'DRAFT') {
                throw new \Exception("Cannot revert a PUBLISHED flow to DRAFT. Use a new version instead.");
            }
        });
    }

    protected $table = 'whatsapp_flows';
    protected $guarded = [];

    public $fillable = ['team_id', 'flow_id', 'name', 'category', 'status', 'design_data', 'flow_json', 'uses_data_endpoint', 'entry_point_config', 'active_version_id', 'latest_version_number'];

    protected $casts = [
        'design_data' => 'array',
        'flow_json' => 'array',
        'entry_point_config' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(WhatsAppFlowResponse::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WhatsAppFlowVersion::class);
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(WhatsAppFlowVersion::class, 'active_version_id');
    }
}
