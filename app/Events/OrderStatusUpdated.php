<?php

namespace App\Events;

use App\Events\Base\DomainEvent;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;

class OrderStatusUpdated extends DomainEvent
{
    use InteractsWithSockets;

    // Transient props for listeners
    public $order;
    public $status;
    public $context;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param string $status
     * @param array $context Additional info
     */
    public function __construct(Order $order, string $status, array $context = [])
    {
        $this->order = $order;
        $this->status = $status;
        $this->context = $context;

        parent::__construct([
            'order_id' => $order->id,
            'status' => $status,
            'context' => $context
        ], [
            'team_id' => $order->team_id,
            'actor_id' => auth()->id(),
        ]);
    }

    public function source(): string
    {
        return 'commerce';
    }

    public function category(): string
    {
        return 'business';
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|integer',
            'status' => 'required|string',
            'context' => 'array'
        ];
    }
}
