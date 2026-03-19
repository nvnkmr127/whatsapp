<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CannedMessage extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $fillable = [
        'team_id',
        'shortcut',
        'content',
    ];
}
