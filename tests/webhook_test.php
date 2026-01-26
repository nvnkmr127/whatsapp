#!/usr/bin/env php
<?php

/**
 * Webhook & OTP Events Test Script
 * 
 * This script tests the webhook and OTP event system.
 * Run: php tests/webhook_test.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Team;
use App\Models\WebhookSubscription;
use App\Services\OTPService;
use App\Services\WebhookService;

echo "🔍 Webhook & OTP Events Test\n";
echo "============================\n\n";

// Test 1: Check if WebhookService uses jobs
echo "1. Testing WebhookService job dispatching...\n";
$webhookService = new WebhookService();
$team = Team::first();

if ($team) {
    echo "   ✓ Found team: {$team->name}\n";

    // Create a test subscription
    $subscription = WebhookSubscription::firstOrCreate([
        'team_id' => $team->id,
        'name' => 'Test Webhook',
        'url' => 'https://webhook.site/test',
    ], [
        'is_active' => true,
        'events' => ['message.sent', 'otp.sent'],
    ]);

    echo "   ✓ Test subscription created/found\n";

    // Dispatch a test event
    try {
        $webhookService->dispatch($team->id, 'message.sent', [
            'test' => true,
            'message' => 'This is a test webhook',
        ]);
        echo "   ✓ Webhook dispatched (check queue for ExecuteOutboundWebhookJob)\n";
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ No teams found in database\n";
}

echo "\n";

// Test 2: Check OTP events
echo "2. Testing OTP event system...\n";
$otpService = new OTPService();

// Check if OTP service has the new methods
$reflection = new \ReflectionClass($otpService);
$sendMethod = $reflection->getMethod('send');
$params = $sendMethod->getParameters();

if (count($params) >= 3) {
    echo "   ✓ OTPService::send() has teamId parameter\n";
} else {
    echo "   ✗ OTPService::send() missing teamId parameter\n";
}

echo "\n";

// Test 3: Check event listeners
echo "3. Checking event listeners...\n";
$events = [
    'App\Events\MessageSent' => 'App\Listeners\SendMessageSentWebhook',
    'App\Events\MessageStatusUpdated' => 'App\Listeners\SendMessageStatusWebhook',
];

foreach ($events as $event => $listener) {
    if (class_exists($event) && class_exists($listener)) {
        echo "   ✓ {$event} → {$listener}\n";
    } else {
        echo "   ✗ Missing: {$event} or {$listener}\n";
    }
}

echo "\n";

// Test 4: Check available webhook events
echo "4. Checking available webhook events...\n";
$webhookManager = new \App\Livewire\Developer\WebhookManager();
$availableEvents = $webhookManager->availableEvents;

$expectedEvents = [
    'message.sent',
    'message.status_updated',
    'otp.sent',
    'otp.verified',
    'otp.failed',
    'billing.threshold_reached',
];

foreach ($expectedEvents as $event) {
    if (isset($availableEvents[$event])) {
        echo "   ✓ {$event}: {$availableEvents[$event]}\n";
    } else {
        echo "   ✗ Missing event: {$event}\n";
    }
}

echo "\n";

// Test 5: Check job exists
echo "5. Checking ExecuteOutboundWebhookJob...\n";
if (class_exists('App\Jobs\ExecuteOutboundWebhookJob')) {
    echo "   ✓ ExecuteOutboundWebhookJob exists\n";

    $job = new \ReflectionClass('App\Jobs\ExecuteOutboundWebhookJob');
    if ($job->hasProperty('tries')) {
        echo "   ✓ Job has retry logic configured\n";
    }
} else {
    echo "   ✗ ExecuteOutboundWebhookJob not found\n";
}

echo "\n";
echo "============================\n";
echo "✅ Test completed!\n";
echo "\nNext steps:\n";
echo "1. Run: php artisan queue:work --queue=webhooks\n";
echo "2. Send a test message to trigger message.sent webhook\n";
echo "3. Check webhook delivery logs in the database\n";
