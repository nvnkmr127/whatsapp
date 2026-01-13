<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignDetail extends Model
{
    protected $guarded = [];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
