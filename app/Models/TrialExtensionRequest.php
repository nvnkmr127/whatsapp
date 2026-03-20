<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrialExtensionRequest extends Model
{
    protected $fillable = ['team_id', 'user_id', 'days', 'reason', 'status', 'admin_notes'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
