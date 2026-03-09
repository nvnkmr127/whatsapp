<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use \App\Traits\HasTeam;

class Order extends Model
{
    use HasTeam;

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

    protected static function booted()
    {
        static::created(function ($order) {
            try {
                if (class_exists(\App\Services\WorkflowEngine::class)) {
                    app(\App\Services\WorkflowEngine::class)->trigger('order_placed', $order, ['amount' => $order->total_amount]);
                }
            } catch (\Exception $e) {
            }
        });

        static::updated(function ($order) {
            try {
                if (class_exists(\App\Services\WorkflowEngine::class)) {
                    if ($order->wasChanged('status') && $order->status === 'paid') {
                        app(\App\Services\WorkflowEngine::class)->trigger('payment_received', $order, ['amount' => $order->total_amount, 'currency' => $order->currency]);
                    }
                }
            } catch (\Exception $e) {
            }
        });
    }

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
