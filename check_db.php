<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$messages = \App\Models\Message::orderBy('id', 'desc')->take(10)->get();
foreach ($messages as $msg) {
    echo "ID: {$msg->id} | Type: {$msg->type} | Dir: {$msg->direction} | WAMID: {$msg->whatsapp_message_id} | ReplyTo: {$msg->reply_to_message_id}\n";
    if ($msg->reply_to_message_id) {
        $reply = $msg->replyTo;
        echo "   -> Replied to: {$reply->id} | Dir: {$reply->direction} | WAMID: {$reply->whatsapp_message_id}\n";
    }
}
