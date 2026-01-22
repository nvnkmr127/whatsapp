<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMergeLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'duplicate_data' => 'array',
        'merged_at' => 'datetime',
        'confidence_score' => 'float',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function primaryContact()
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function mergedBy()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }
}
