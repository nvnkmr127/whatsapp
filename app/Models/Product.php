<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'team_id',
        'category_id',
        'name',
        'description',
        'price',
        'currency',
        'retailer_id',
        'image_url',
        'meta_product_id',
        'url',
        'availability'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
