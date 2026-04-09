<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'deal_id',
        'name',
        'description',
        'unit_price',
        'quantity',
        'discount',
        'tax',
        'total',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($product) {
            $product->calculateTotal();
        });
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    protected function calculateTotal(): void
    {
        $subtotal = $this->unit_price * $this->quantity;
        $discountAmount = $subtotal * ($this->discount / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($this->tax / 100);

        $this->total = $subtotal - $discountAmount + $taxAmount;
    }
}
