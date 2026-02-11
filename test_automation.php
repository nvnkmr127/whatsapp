<?php

use App\Models\Message;
use App\Models\Contact;
use App\Events\MessageReceived;
use App\Listeners\AutomationTriggerListener;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting Manual Automation Test...\n";

$message = Message::with(['contact', 'team'])->orderBy('id', 'desc')->first();
if (!$message) {
    die("No messages found in DB.\n");
}

echo "Testing with Message ID: {$message->id} for Team: {$message->team->name}\n";
echo "Content: {$message->content}\n";

$listener = new AutomationTriggerListener();
$event = new MessageReceived($message);

try {
    echo "Calling listener handle()...\n";
    $listener->handle($event);
    echo "Listener handle() completed.\n";
} catch (\Exception $e) {
    echo "Error calling listener: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
