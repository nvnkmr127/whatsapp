<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactField extends Model
{
    use \App\Traits\HasTeam;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];
}
