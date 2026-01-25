<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Integration;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WooCommerceWebhookController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Handle WooCommerce Webhook.
     * Route: POST /webhooks/woocommerce/orders
     * Topic: order.updated, order.created
     */
    public function handle(Request $request)
    {
        // 1. Verify Signature
        $signature = $request->header('X-WC-Webhook-Signature');
        $data = $request->getContent();

        $webhookSource = $request->header('X-WC-Webhook-Source'); // http://example.com/

        if (!$webhookSource) {
            return response()->json(['message' => 'Missing Source Header'], 400);
        }

        // Normalize domain
        $domain = parse_url($webhookSource, PHP_URL_HOST) ?? $webhookSource;

        $integration = Integration::where('type', 'woocommerce')
            ->where('status', '!=', \App\Enums\IntegrationState::SUSPENDED->value)
            ->where('credentials->url', 'LIKE', "%$domain%")
            ->first();

        if (!$integration) {
            Log::info("Received WC webhook for unknown or suspended shop: {$domain}");
            return response()->json(['message' => 'Shop not integrated'], 200);
        }

        // Verify Secret if configured
        $secret = $integration->webhook_secret ?? config('services.woocommerce.webhook_secret');
        if ($secret && $signature) {
            $expectedSignature = base64_encode(hash_hmac('sha256', $data, $secret, true));
            if (!hash_equals($signature, $expectedSignature)) {
                Log::error("WooCommerce Webhook Signature mismatch for integration: {$integration->id}");
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Log pulse
        $integration->update(['last_webhook_received_at' => now()]);

        $payload = $request->all();
        $orderId = $payload['id'] ?? null;
        if (!$orderId) {
            return response()->json(['message' => 'No Order ID'], 200);
        }

        // Map Status
        // WC Statuses: pending, processing, on-hold, completed, cancelled, refunded, failed
        $wcStatus = $payload['status'] ?? 'pending';

        $status = match ($wcStatus) {
            'processing' => 'placed', // or confirmed
            'completed' => 'delivered', // WC 'completed' usually means done. Sometimes shipped. Let's map to delivered for now or 'shipped'.
            'on-hold' => 'confirmed',
            'cancelled' => 'cancelled',
            'refunded' => 'returned',
            'failed' => 'payment_failed',
            default => 'placed'
        };

        // Note: WC doesn't have a standard 'shipped' status in core. 
        // It relies on 'completed' or plugins. 
        // If 'completed', let's assume 'shipped' + 'delivered' logic? 
        // Let's treat 'completed' as 'shipped' as it's the final action a merchant takes.
        if ($wcStatus === 'completed') {
            $status = 'shipped';
        }

        // Customer
        $billing = $payload['billing'] ?? [];
        $email = $billing['email'] ?? null;
        $phone = $billing['phone'] ?? null;

        if (!$phone) {
            return response()->json(['message' => 'No Customer Phone'], 200);
        }

        $contact = Contact::firstOrCreate(
            ['team_id' => $integration->team_id, 'phone_number' => $phone],
            ['name' => $billing['first_name'] . ' ' . $billing['last_name'], 'email' => $email]
        );

        $order = Order::updateOrCreate(
            [
                'team_id' => $integration->team_id,
                'order_id' => (string) $orderId,
            ],
            [
                'contact_id' => $contact->id,
                'status' => $status,
                'total_amount' => $payload['total'] ?? 0,
                'currency' => $payload['currency'] ?? 'USD',
                'items' => $payload['line_items'] ?? [],
                'payment_details' => ['method' => $payload['payment_method'] ?? 'woocommerce']
            ]
        );

        // Tracking info?
        // WC Shipment Tracking plugin puts it in `meta_data`.
        $context = [];
        if ($status === 'shipped') {
            $meta = $payload['meta_data'] ?? [];
            // Look for common tracking keys
            // This is simplified extraction
        }

        $this->orderService->updateStatus($order, $status, array_merge($context, ['system' => true]));

        return response()->json(['message' => 'Processed'], 200);
    }
}
