<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamInvoiceItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(TeamInvoice::class, 'team_invoice_id');
    }
}
