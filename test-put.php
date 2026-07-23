<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['filesystems.disks.r2.throw' => true]);

try {
    $result = Storage::disk('r2')->put('team-logos/test-string.txt', 'hello');
    echo "Result: " . var_export($result, true) . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
