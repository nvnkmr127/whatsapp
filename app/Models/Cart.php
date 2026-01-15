<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'uuid',
        'team_id',
        'contact_id',
        'items',
        'total_amount',
        'currency',
        'status',
        'context_key',
        'expires_at',
        'reminder_sent_at',
        'meta_data'
    ];

    protected $casts = [
        'items' => 'array',
        'meta_data' => 'array',
        'expires_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '<', now());
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get items as CartItem objects collection
     */
    public function getCartItems()
    {
        $items = $this->items ?? [];
        return collect($items)->map(function ($item) {
            return CartItem::fromArray($item);
        });
    }

    public function addItem(CartItem $newItem)
    {
        $items = $this->getCartItems();

        // Check if product already exists
        $existingItem = $items->firstWhere('product_id', $newItem->product_id);

        if ($existingItem) {
            $existingItem->quantity += $newItem->quantity;
        } else {
            $items->push($newItem);
        }

        $this->items = $items->map->toArray()->values()->all();
        $this->save();
    }

    public function removeItem($productId)
    {
        $items = $this->getCartItems();
        $items = $items->reject(function ($item) use ($productId) {
            return $item->product_id == $productId;
        });

        $this->items = $items->map->toArray()->values()->all();
        $this->save();
    }

    public function clear()
    {
        $this->items = [];
        $this->total_amount = 0;
        $this->save();
    }
}
