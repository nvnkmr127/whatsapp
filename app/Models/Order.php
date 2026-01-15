<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'team_id',
        'order_id', // Meta Order ID
        'contact_id',
        'items',
        'total_amount',
        'currency',
        'status',
        'payment_details'
    ];

    protected $casts = [
        'items' => 'array',
        'payment_details' => 'array',
        'status' => 'string', // pending, paid, shipped, cancelled, returned
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at', 'desc');
    }


}
