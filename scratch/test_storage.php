<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$path = 'whatsapp/test/test.txt';
$content = 'Testing storage';

echo "Attempting to write to public disk...\n";
$result = Storage::disk('public')->put($path, $content);

if ($result) {
    echo "Success! File written to: " . Storage::disk('public')->path($path) . "\n";
} else {
    echo "Failed to write to public disk.\n";
}

$url = Storage::disk('public')->url($path);
echo "URL: " . $url . "\n";
