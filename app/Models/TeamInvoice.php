<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function items()
    {
        return $this->hasMany(TeamInvoiceItem::class);
    }
}
