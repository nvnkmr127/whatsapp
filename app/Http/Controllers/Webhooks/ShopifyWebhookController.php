<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Integration;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Handle Shopify Order Updates.
     * Route: POST /webhooks/shopify/orders/updated
     */
    public function handle(Request $request)
    {
        // 1. Verify HMAC Header (Simplification for now, strictly should verify X-Shopify-Hmac-Sha256)
        // Ignoring signature verification for MVP/Demo speed, but crucial for Prod.

        $payload = $request->all();
        // Typically shop domain helps identify the integration, but usually webhooks are scoped by app.
        // We might need to look up the Integration by the shop domain if provided in header or payload.
        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        if (!$shopDomain) {
            Log::warning("Shopify Webhook missing Shop Domain.");
            return response()->json(['error' => 'Missing Shop Domain'], 400);
        }

        // Find the Team/Integration associated with this Shop
        // We need to look into Integrations where credentials['shop_url'] matches.
        // Optimization: In real world, we'd have a `shop_domain` column on integrations table.
        // MVP: Iterate active Shopify integrations.
        $integration = Integration::where('type', 'shopify')
            ->get()
            ->first(function ($int) use ($shopDomain) {
                return str_contains($int->credentials['shop_url'] ?? '', $shopDomain);
            });

        if (!$integration) {
            Log::info("Received Shopify webhook for unknown shop: {$shopDomain}");
            return response()->json(['message' => 'Shop not integrated'], 200);
        }

        $teamId = $integration->team_id;
        $orderData = $payload;
        $shopifyId = $orderData['id'] ?? null;

        if (!$shopifyId) {
            return response()->json(['message' => 'No Order ID'], 200);
        }

        // Map Shopify Status to Internal Status
        // Shopify: financial_status (paid, pending), fulfillment_status (fulfilled, null)
        $status = 'placed'; // Default

        if (!empty($orderData['cancel_reason'])) {
            $status = 'cancelled';
        } elseif ($orderData['fulfillment_status'] === 'fulfilled') {
            $status = 'shipped'; // or delivered, depending on carrier info? Usually 'shipped'.
        } elseif ($orderData['financial_status'] === 'paid') {
            $status = 'confirmed';
        }

        // Find or Create Order
        // Note: We might be creating an order that didn't originate from our Cart flow.
        // That's totally fine. We want to capture it.

        $customer = $orderData['customer'] ?? [];
        $email = $customer['email'] ?? null;
        $phone = $customer['phone'] ?? $customer['default_address']['phone'] ?? null;

        if (!$phone) {
            // Can't send WA without phone.
            return response()->json(['message' => 'No Customer Phone'], 200);
        }

        // Resolve Contact
        $contact = Contact::firstOrCreate(
            ['team_id' => $teamId, 'phone_number' => $phone],
            ['name' => $customer['first_name'] . ' ' . $customer['last_name'], 'email' => $email]
        );

        $order = Order::updateOrCreate(
            [
                'team_id' => $teamId,
                'order_id' => (string) $shopifyId, // Using Shopify ID as Order ID
            ],
            [
                'contact_id' => $contact->id,
                'status' => $status,
                'total_amount' => $orderData['total_price'] ?? 0,
                'currency' => $orderData['currency'] ?? 'USD',
                'items' => $orderData['line_items'] ?? [], // Store raw items for reference
                'payment_details' => ['method' => 'shopify', 'financial_status' => $orderData['financial_status'] ?? null]
            ]
        );

        // TRIGGER THE LIFECYCLE EVENT!
        // Prepare context (Tracking info)
        $context = [];
        if ($status === 'shipped') {
            $fulfillments = $orderData['fulfillments'] ?? [];
            if (!empty($fulfillments)) {
                $lastFulfillment = end($fulfillments);
                $context['tracking_number'] = $lastFulfillment['tracking_number'] ?? null;
                $context['tracking_url'] = $lastFulfillment['tracking_url'] ?? null;
            }
        }

        // Only fire if status CHANGED? updateOrCreate returns the model. 
        // We should check if was changed.
        // Laravel's `wasChanged()` checks immediate save.
        // But for `updateStatus` service logic, we explicitly want to fire logic.
        // Let's call updateStatus.

        // However, we just updated it above. Let's separate Create vs trigger.
        // Actually, best practice: `updateOrCreate` saves.
        // We can manually dispatch the event using our Service helper.

        $this->orderService->updateStatus($order, $status, $context);

        return response()->json(['message' => 'Processed'], 200);
    }
}
