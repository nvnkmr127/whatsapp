<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // 'proof_url' is now available

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
