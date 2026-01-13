<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowSession extends Model
{
    /** @use HasFactory<\Database\Factories\FlowSessionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'state' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
