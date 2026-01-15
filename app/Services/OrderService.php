<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Convert Cart to Order
     */
    public function createOrderFromCart(Cart $cart, $paymentDetails = [])
    {
        $team = $cart->contact->team;
        $config = $team->commerce_config ?? [];

        // 1. Check Min Order Value
        $minOrder = $config['min_order_value'] ?? 0;
        if ($cart->total_amount < $minOrder) {
            throw new \Exception("Order total must be at least {$cart->currency} {$minOrder}");
        }

        // 2. Check COD Eligibility if applicable
        // Assuming payment_details['method'] exists
        if (isset($paymentDetails['method']) && $paymentDetails['method'] === 'cod') {
            if (empty($config['cod_enabled'])) {
                throw new \Exception("Cash on Delivery is not enabled for this store.");
            }
        }

        $order = Order::create([
            'team_id' => $team->id ?? auth()->user()->currentTeam->id,
            'contact_id' => $cart->contact_id,
            'order_id' => 'ORD-' . strtoupper(Str::random(10)), // Internal ID
            'items' => $cart->items,
            'total_amount' => $cart->total_amount,
            'currency' => $cart->currency,
            'status' => 'placed',
            'payment_details' => $paymentDetails,
        ]);

        // Clear Cart
        $cart->clear();

        // Dispatch Event
        OrderStatusUpdated::dispatch($order, 'placed');

        // Log Event
        $this->logEvent($order, 'placed', ['amount' => $cart->total_amount]);

        return $order;
    }

    /**
     * Update Order Status
     */
    public function updateStatus(Order $order, string $status, array $context = [])
    {
        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        // Dispatch Event
        OrderStatusUpdated::dispatch($order, $status, $context);

        // Log Event
        $this->logEvent($order, $status, array_merge($context, ['previous_status' => $oldStatus]));

        return $order;
    }

    protected function logEvent(Order $order, string $event, array $metadata = [])
    {
        $order->events()->create([
            'event' => $event,
            'metadata' => $metadata
        ]);
    }
}
