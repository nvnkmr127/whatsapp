<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'performed_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relatedTo()
    {
        return $this->morphTo();
    }
}
