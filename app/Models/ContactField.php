<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactField extends Model
{
    use HasFactory;
    use \App\Traits\HasTeam;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];
}
