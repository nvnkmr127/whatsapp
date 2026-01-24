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

        // 0. Commerce Readiness Check
        $readinessService = app(\App\Services\CommerceReadinessService::class);
        $readiness = $readinessService->evaluate($team);
        if ($readiness['state'] === \App\Services\CommerceReadinessService::STATE_BLOCKED) {
            throw new \Exception("Commerce service is currently unavailable for this store. Please contact the administrator.");
        }

        // 1. Check Min Order Value
        $minOrder = $config['min_order_value'] ?? 0;
        if ($cart->total_amount < $minOrder) {
            throw new \Exception("Order total must be at least {$cart->currency} {$minOrder}");
        }

        // 2. Check COD Eligibility if applicable
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
            'status' => 'created',
            'payment_details' => $paymentDetails,
        ]);

        // Clear Cart
        $cart->clear();

        // Trigger automation for order received
        try {
            $whatsappService = new WhatsAppService();
            $automationService = new AutomationService($whatsappService);
            $automationService->checkSpecialTriggers($cart->contact, 'order_received');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order Received Automation Trigger Failed: ' . $e->getMessage());
        }

        // Dispatch Event
        OrderStatusUpdated::dispatch($order, 'created');

        // Log Event
        $this->logEvent($order, 'created', ['amount' => $cart->total_amount]);

        return $order;
    }

    /**
     * Update Order Status
     */
    public function updateStatus(Order $order, string $status, array $context = [])
    {
        $user = $context['user'] ?? auth()->user();
        if (!$user) {
            throw new \Exception("Authentication required to update order status.");
        }

        // Validate Lifecycle
        $lifecycleService = app(\App\Services\CommerceLifecycleService::class);
        $check = $lifecycleService->canTransition($order, $status, $user);

        if (!$check['allowed']) {
            throw new \Exception($check['message']);
        }

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
