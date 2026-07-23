<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

file_put_contents('test.tmp', 'hello world');
$stream = fopen('test.tmp', 'r');

config(['filesystems.disks.r2.throw' => true]);

try {
    $result = Storage::disk('r2')->put('team-logos/test.txt', $stream);
    echo "Result: " . var_export($result, true) . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
