<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $status;
    public $context;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param string $status
     * @param array $context Additional info like tracking number, return reasons
     */
    public function __construct(Order $order, string $status, array $context = [])
    {
        $this->order = $order;
        $this->status = $status;
        $this->context = $context;
    }
}
