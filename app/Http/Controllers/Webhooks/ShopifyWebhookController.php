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
        // 1. Verify HMAC Header
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $secret = config('services.shopify.webhook_secret'); // Should be per integration ideally

        if (! $hmac) {
            Log::warning('Shopify Webhook rejected: missing X-Shopify-Hmac-Sha256 header.');

            return response()->json(['error' => 'Missing signature'], 401);
        }

        if (! $secret) {
            Log::critical('Shopify Webhook rejected: services.shopify.webhook_secret not configured.');

            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));
        if (! hash_equals($hmac, $calculatedHmac)) {
            Log::error("Shopify Webhook HMAC mismatch for shop: {$request->header('X-Shopify-Shop-Domain')}");

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');

        if (! $shopDomain) {
            Log::warning('Shopify Webhook missing Shop Domain.');

            return response()->json(['error' => 'Missing Shop Domain'], 400);
        }

        // Optimization: Find by credentials domain directly if possible
        // Better: Use a dedicated 'shop_domain' column or cache.
        $integration = Integration::where('type', 'shopify')
            ->where('status', 'active')
            ->get()
            ->first(function ($int) use ($shopDomain) {
                return str_contains($int->credentials['domain'] ?? ($int->credentials['shop_url'] ?? ''), $shopDomain);
            });

        if (! $integration) {
            Log::info("Received Shopify webhook for unknown or inactive shop: {$shopDomain}");

            return response()->json(['message' => 'Shop not integrated or integration inactive'], 200);
        }

        $payload = $request->all();
        $teamId = $integration->team_id;
        $orderData = $payload;
        $shopifyId = $orderData['id'] ?? null;

        if (! $shopifyId) {
            return response()->json(['message' => 'No Order ID'], 200);
        }

        // Map Shopify Status to Internal Status
        // Shopify: financial_status (paid, pending), fulfillment_status (fulfilled, null)
        $status = 'placed'; // Default

        if (! empty($orderData['cancel_reason'])) {
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

        if (! $phone) {
            // Can't send WA without phone.
            return response()->json(['message' => 'No Customer Phone'], 200);
        }

        $resolvedName = trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? ''));
        if (empty($resolvedName)) {
            $resolvedName = 'Shopify Customer';
        }

        $contact = Contact::where('team_id', $teamId)
            ->where('phone_number', $phone)
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'team_id' => $teamId,
                'phone_number' => $phone,
                'name' => $resolvedName,
                'email' => $email,
            ]);
        } else {
            $updates = [];
            if (empty($contact->email) && $email) {
                $updates['email'] = $email;
            }

            // Check if current name is placeholder or phone number
            $currentName = $contact->name;
            $placeholders = ['Webhook Contact', 'Friend', 'Shopify Customer', 'WooCommerce Customer'];
            $cleanPhone = preg_replace('/[^\d]/', '', $currentName ?? '');
            $isPhoneName = ($cleanPhone !== '' && ($currentName === $cleanPhone || '+' . $cleanPhone === $currentName));

            if (empty($currentName) || in_array($currentName, $placeholders) || $isPhoneName) {
                if ($resolvedName && !in_array($resolvedName, $placeholders)) {
                    $updates['name'] = $resolvedName;
                }
            }

            if (! empty($updates)) {
                $contact->update($updates);
            }
        }

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
                'payment_details' => ['method' => 'shopify', 'financial_status' => $orderData['financial_status'] ?? null],
            ]
        );

        // TRIGGER THE LIFECYCLE EVENT!
        // Prepare context (Tracking info)
        $context = [];
        if ($status === 'shipped') {
            $fulfillments = $orderData['fulfillments'] ?? [];
            if (! empty($fulfillments)) {
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
