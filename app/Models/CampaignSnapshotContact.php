<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignSnapshotContact extends Model
{
    protected $guarded = [];

    public function snapshot()
    {
        return $this->belongsTo(CampaignSnapshot::class, 'snapshot_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
