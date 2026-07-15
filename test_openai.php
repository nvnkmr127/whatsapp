<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = get_setting("ai_openai_api_key_1");
if (!$key) { echo "No key\n"; exit; }

foreach (['gpt-4o', '', null, 'test'] as $model) {
    echo "Testing model: " . var_export($model, true) . "\n";
    $response = Illuminate\Support\Facades\Http::withToken($key)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => 'hi']]
        ]);
    echo $response->body() . "\n\n";
}
