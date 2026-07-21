<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$messages = \App\Models\Message::orderBy('id', 'desc')->take(2)->get();
foreach($messages as $msg) {
    echo "ID: " . $msg->id . " | Content: " . $msg->content . " | ReplyTo: " . $msg->reply_to_message_id . " | Direction: " . $msg->direction . "\n";
    if ($msg->replyTo) {
        echo "   -> ReplyToContent: " . $msg->replyTo->content . "\n";
    }
}
