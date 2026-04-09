<?php

namespace App\Models;

use App\Traits\HasTeam;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasTeam;

    protected $guarded = [];
}
