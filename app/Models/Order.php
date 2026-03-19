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
        static::saving(function ($order) {
            if (!$order->isDirty('status')) {
                return;
            }

            $oldStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            // Terminal Status Restriction
            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                throw new \Exception("Cannot transition out of terminal state: cancelled");
            }

            if ($oldStatus === 'shipped' && in_array($newStatus, ['pending', 'paid'])) {
                 throw new \Exception("Cannot revert order to {$newStatus} once it has been shipped.");
            }

            if ($oldStatus === 'paid' && $newStatus === 'pending') {
                 throw new \Exception("Cannot revert paid order to pending status.");
            }
        });

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
                    // Only trigger on initial transition to 'paid' from 'pending'
                    $wasPending = $order->getOriginal('status') === 'pending';
                    if ($order->wasChanged('status') && $order->status === 'paid' && $wasPending) {
                        app(\App\Services\WorkflowEngine::class)->trigger('payment_received', $order, [
                            'amount' => $order->total_amount, 
                            'currency' => $order->currency,
                            'transition' => 'pending_to_paid'
                        ]);
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
