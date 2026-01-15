<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Integration;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CustomSiteWebhookController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Handle Custom Site Order Updates.
     * Route: POST /webhooks/custom/orders
     * Headers: X-Integration-Token
     */
    public function handle(Request $request)
    {
        // 1. Authenticate
        $token = $request->header('X-Integration-Token');
        if (!$token) {
            return response()->json(['error' => 'Missing X-Integration-Token'], 401);
        }

        $integration = Integration::where('type', 'custom')
            ->where('status', 'active')
            ->get() // Iterate locally due to encryption
            ->first(function ($int) use ($token) {
                return ($int->credentials['api_key'] ?? '') === $token;
            });

        if (!$integration) {
            return response()->json(['error' => 'Invalid Token'], 401);
        }

        $payload = $request->validate([
            'order_id' => 'required|string',
            'status' => 'required|string', // placed, shipped, etc.
            'customer.phone' => 'required|string',
            'customer.name' => 'nullable|string',
            'customer.email' => 'nullable|email',
            'total_amount' => 'required|numeric',
            'currency' => 'nullable|string',
            'tracking_number' => 'nullable|string',
            'tracking_url' => 'nullable|url',
        ]);

        $teamId = $integration->team_id;

        // 2. Resolve Contact
        $contact = Contact::firstOrCreate(
            ['team_id' => $teamId, 'phone_number' => $payload['customer']['phone']],
            ['name' => $payload['customer']['name'] ?? 'Friend', 'email' => $payload['customer']['email']]
        );

        // 3. Update Order
        $order = Order::updateOrCreate(
            [
                'team_id' => $teamId,
                'order_id' => $payload['order_id'],
            ],
            [
                'contact_id' => $contact->id,
                'status' => $payload['status'], // Explicit mapping expected from custom site
                'total_amount' => $payload['total_amount'],
                'currency' => $payload['currency'] ?? 'USD',
                'items' => [], // Optional for custom
            ]
        );

        // 4. Trigger Notification
        $context = [
            'tracking_number' => $payload['tracking_number'] ?? null,
            'tracking_url' => $payload['tracking_url'] ?? null,
        ];

        $this->orderService->updateStatus($order, $payload['status'], $context);

        return response()->json(['message' => 'Processed'], 200);
    }
}
