<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortLinkClick extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function shortLink()
    {
        return $this->belongsTo(ShortLink::class);
    }
}
