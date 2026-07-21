<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$team = \App\Models\Team::first();
$contact = \App\Models\Contact::first();
$conversation = \App\Models\Conversation::first();

// Create an original inbound message
$originalId = 'wamid.' . Str::random(10);
$originalMessage = \App\Models\Message::create([
    'team_id' => $team->id,
    'contact_id' => $contact->id,
    'conversation_id' => $conversation->id,
    'direction' => 'inbound',
    'type' => 'text',
    'content' => 'First message',
    'whatsapp_message_id' => $originalId,
]);

echo "Created original message with ID: {$originalMessage->id}\n";

// Mock a reply payload
$payload = [
    'provider_id' => 'wamid.' . Str::random(10),
    'from_phone' => $contact->phone_number,
    'to_phone_id' => $team->whatsapp_phone_number_id,
    'message_type' => 'text',
    'content' => [
        'from' => $contact->phone_number,
        'id' => 'wamid.' . Str::random(10),
        'timestamp' => time(),
        'type' => 'text',
        'text' => ['body' => 'This is a reply'],
        'context' => [
            'id' => $originalId,
            'from' => $contact->phone_number
        ]
    ]
];

$job = new \App\Jobs\PersistMessageJob($payload, 'trace123');
try {
    $job->handle();
    echo "Job executed successfully!\n";
    $replyMsg = \App\Models\Message::where('whatsapp_message_id', $payload['provider_id'])->first();
    echo "Reply message ID: {$replyMsg->id}\n";
    echo "Reply to message ID: {$replyMsg->reply_to_message_id}\n";
    echo "Relation works? " . ($replyMsg->replyTo ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "Job failed: " . $e->getMessage() . "\n";
}
