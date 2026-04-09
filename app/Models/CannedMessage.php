<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CannedMessage extends Model
{
    use \App\Traits\HasTeam;
    use HasFactory;

    protected $fillable = [
        'team_id',
        'shortcut',
        'content',
    ];
}
