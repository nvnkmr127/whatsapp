<?php

namespace App\Models;



class CartItem
{
    public $product_id;
    public $quantity;
    public $price;
    public $metadata;

    public function __construct($product_id, $quantity, $price, $metadata = [])
    {
        $this->product_id = $product_id;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->metadata = $metadata;
    }

    public static function fromArray(array $data)
    {
        return new self(
            $data['product_id'],
            $data['quantity'] ?? 1,
            $data['price'] ?? 0,
            $data['metadata'] ?? []
        );
    }

    public function toArray()
    {
        return [
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'metadata' => $this->metadata,
        ];
    }
}
