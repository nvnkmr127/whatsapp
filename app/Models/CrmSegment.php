<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmSegment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'filters' => 'json',
        'is_shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
