<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'template_variables' => 'array',
        'header_params' => 'array',
        'footer_params' => 'array',
        'meta' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contacts()
    {
        return $this->hasMany(CampaignSnapshotContact::class, 'snapshot_id');
    }
}
