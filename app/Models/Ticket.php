<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use \App\Traits\HasTeam;

class Ticket extends Model
{
    use HasTeam;

    protected $guarded = [];
}
