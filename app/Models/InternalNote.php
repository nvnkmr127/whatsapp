<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use \App\Traits\HasTeam;

class InternalNote extends Model
{
    use HasTeam;

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
