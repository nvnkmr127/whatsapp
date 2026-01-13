<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalNote extends Model
{
    protected $guarded = [];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
