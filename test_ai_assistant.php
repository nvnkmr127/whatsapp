<?php

use App\Models\User;
use App\Models\Team;
use App\Models\Contact;
use App\Models\Product;
use App\Services\AiCommerceService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

putenv('OPENAI_API_KEY=sk-dummy-key');

// 1. Setup Context
$user = User::first();
$team = $user->currentTeam;

// Ensure team has dummy WhatsApp credentials for testing
$team->update([
    'whatsapp_access_token' => 'dummy-token',
    'whatsapp_phone_number_id' => 'dummy-phone-id',
    'whatsapp_business_account_id' => 'dummy-waba-id'
]);

$contact = Contact::firstOrCreate([
    'team_id' => $team->id,
    'phone_number' => '1234567890',
    'name' => 'Test User'
]);

// 1.5 Open 24h Window by creating an inbound message
\App\Models\Message::create([
    'team_id' => $team->id,
    'contact_id' => $contact->id,
    'conversation_id' => 1, // Dummy
    'whatsapp_message_id' => 'initial-msg-' . time(),
    'direction' => 'inbound',
    'type' => 'text',
    'content' => 'Hello AI',
    'status' => 'delivered'
]);
$contact->update(['last_interaction_at' => now()]);

// 2. Clear previous products and create test products
Product::where('team_id', $team->id)->delete();
Product::create([
    'team_id' => $team->id,
    'name' => 'Hiking Boots',
    'price' => 120,
    'availability' => 'in stock',
    'description' => 'Perfect for long mountain hikes. Waterproof and durable.',
    'image_url' => 'https://via.placeholder.com/150?text=Boots'
]);
Product::create([
    'team_id' => $team->id,
    'name' => 'Climbing Rope',
    'price' => 80,
    'availability' => 'in stock',
    'description' => '60m dynamic rope for rock climbing.',
    'image_url' => 'https://via.placeholder.com/150?text=Rope'
]);

// 3. Configure Centralized AI Settings (in settings table)
$teamId = $team->id;
\App\Models\Setting::updateOrCreate(
    ['key' => "ai_persona_{$teamId}"],
    ['value' => "You are a specialized mountain guide assistant.", 'group' => 'ai_settings']
);
\App\Models\Setting::updateOrCreate(
    ['key' => "ai_openai_api_key_{$teamId}"],
    ['value' => 'sk-dummy-key-from-db', 'group' => 'ai_settings']
);

// Enable via commerce config (master switch)
$config = $team->commerce_config ?? [];
$config['ai_assistant_enabled'] = true;
$team->forceFill(['commerce_config' => $config])->save();

echo "Configured Centralized AI Settings for Team: {$team->name}\n";

// 4. Mock OpenAI Response (to test logic without real API key)
Http::fake([
    'api.openai.com/*' => Http::response([
        'choices' => [
            [
                'message' => [
                    'content' => json_encode([
                        'matched' => true,
                        'product_ids' => Product::where('name', 'Hiking Boots')->pluck('id')->toArray(),
                        'reply_text' => "Since you're interested in hiking, I highly recommend our waterproof boots!"
                    ])
                ]
            ]
        ]
    ], 200)
]);

// 5. Trigger AI Handler
$aiService = new AiCommerceService(new WhatsAppService());
$message = "I'm looking for some hiking gear, specifically boots.";

echo "Simulating user message: '{$message}'\n";

try {
    $handled = $aiService->handle($contact, $message);
    if ($handled) {
        echo "AI Assistant successfully handled the message.\n";
    } else {
        echo "AI Assistant failed to handle the message.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Check logs for 'Triggering Customer WhatsApp' and 'sendMedia' calls.\n";
