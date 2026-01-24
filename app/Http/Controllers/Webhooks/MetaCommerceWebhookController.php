<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Models\Product;
use App\Models\Order;
use App\Models\Contact;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetaCommerceWebhookController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    /**
     * Handle Meta Webhook Verification (GET).
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.meta.webhook_verify_token', 'meta-catalog-secret');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle Meta Webhook Notifications (POST).
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info("MetaCommerceWebhook: Received event", ['payload' => $payload]);

        // Meta webhooks usually have an 'entry' array
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                switch ($field) {
                    case 'catalog_item_validation_status':
                        $this->handleItemValidation($value);
                        break;

                    case 'orders':
                        $this->handleOrderEvent($value);
                        break;
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    protected function handleItemValidation($value)
    {
        $retailerId = $value['retailer_id'] ?? null;
        $status = $value['status'] ?? null; // e.g., 'rejected', 'approved'
        $errors = $value['errors'] ?? [];

        if ($retailerId) {
            Product::where('retailer_id', $retailerId)->update([
                'sync_state' => $status === 'approved' ? 'synced' : 'failed',
                'sync_errors' => !empty($errors) ? json_encode($errors) : null
            ]);
        }
    }

    protected function handleOrderEvent($value)
    {
        // Meta Order Payloads usually contain:
        // 'order_id', 'buyer_info', 'items', 'total_amount', etc.
        $externalOrderId = $value['id'] ?? $value['order_id'] ?? null;
        if (!$externalOrderId)
            return;

        // Since credentials are encrypted in the DB, we fetch all meta_commerce integrations
        // and find the one with the matching catalog_id in PHP.
        $integration = Integration::where('type', 'meta_commerce')
            ->get()
            ->first(function ($int) use ($value) {
                return ($int->credentials['catalog_id'] ?? '') === ($value['catalog_id'] ?? '');
            });

        if (!$integration) {
            Log::warning("MetaCommerceWebhook: Could not find integration for catalog_id: " . ($value['catalog_id'] ?? 'null'));
            return;
        }

        $teamId = $integration->team_id;
        $buyer = $value['buyer'] ?? [];
        $phone = $buyer['phone'] ?? null;

        if (!$phone) {
            Log::warning("MetaCommerceWebhook: Order missing buyer phone");
            return;
        }

        // Resolve or Create Contact
        $contact = Contact::firstOrCreate(
            ['team_id' => $teamId, 'phone_number' => $phone],
            ['name' => ($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? '')]
        );

        // Map Meta status to internal status
        $statusMap = [
            'CREATED' => 'placed',
            'PENDING' => 'placed',
            'COMPLETED' => 'confirmed',
            'SHIPPED' => 'shipped',
            'CANCELLED' => 'cancelled',
        ];

        $status = $statusMap[$value['status'] ?? 'CREATED'] ?? 'placed';

        $order = Order::updateOrCreate(
            [
                'team_id' => $teamId,
                'order_id' => (string) $externalOrderId,
            ],
            [
                'contact_id' => $contact->id,
                'status' => $status,
                'total_amount' => $value['total_amount'] ?? 0,
                'currency' => $value['currency'] ?? 'USD',
                'items' => $value['items'] ?? [],
                'payment_details' => [
                    'method' => 'meta_shop',
                    'meta_order_id' => $externalOrderId,
                    'catalog_id' => $value['catalog_id'] ?? null
                ]
            ]
        );

        $this->orderService->updateStatus($order, $status);
    }
}
