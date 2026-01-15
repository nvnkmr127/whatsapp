<?php

use App\Models\User;
use App\Models\Team;
use App\Models\Contact;
use App\Models\Cart;
use App\Models\Product;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Setup Context
$user = User::first();
$team = $user->currentTeam;
$contact = Contact::factory()->create(['team_id' => $team->id]);

// 2. Configure Settings (Enable Agent Alert for 'placed')
$config = $team->commerce_config ?? [];
$config['cod_enabled'] = true;
$config['agent_notifications']['placed'] = true;
$team->forceFill(['commerce_config' => $config])->save();

echo "Configured Agent Alerts for Team: {$team->name}\n";

// 3. Create Cart & Order
$cartService = new CartService();
$cart = $cartService->getOrCreateCart($contact, $team);
$product = Product::create([
    'team_id' => $team->id,
    'name' => 'Test Product',
    'price' => 100,
    'availability' => 'in stock',
    'image_url' => 'http://example.com/image.jpg'
]);
$cartService->addItem($cart, $product, 1);

echo "Cart Created with Total: {$cart->total_amount}\n";

$orderService = new OrderService();
try {
    $order = $orderService->createOrderFromCart($cart, ['method' => 'cod']);
    echo "Order Created: {$order->order_id}\n";
} catch (\Exception $e) {
    echo "Error creating order: " . $e->getMessage() . "\n";
    exit;
}

// 4. Verify Order Events
$events = $order->events;
echo "Order Events Count: " . $events->count() . "\n";
foreach ($events as $e) {
    echo " - Event: {$e->event} at {$e->created_at}\n";
}

// 5. Update Status
$orderService->updateStatus($order, 'shipped', ['tracking' => 'TRK123']);
echo "Updated Status to Shipped.\n";

$order->refresh();
foreach ($order->events as $e) {
    echo " - Event: {$e->event} (Meta: " . json_encode($e->metadata) . ")\n";
}
