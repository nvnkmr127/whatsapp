<?php
/**
 * Laravel Post-Deployment Helper for Shared Hosting
 * 
 * Instructions:
 * 1. Upload this file to your 'public' folder.
 * 2. Update the $secret key below.
 * 3. Access via: yourdomain.com/deploy.php?key=YOUR_SECRET&cmd=migrate
 */

// --- CONFIGURATION ---
$secret = 'update_this_to_something_secure';
// ---------------------

if (($_GET['key'] ?? '') !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    die('Unauthorized access.');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

$allowedCommands = [
    'migrate' => ['migrate', ['--force' => true]],
    'seed' => ['db:seed', ['--class' => 'PlanSeeder', '--force' => true]],
    'link' => ['storage:link', []],
    'cache' => ['optimize', []], // Clears and caches config, routes, and views
    'clear' => ['optimize:clear', []],
];

$cmdKey = $_GET['cmd'] ?? 'list';

if (!array_key_exists($cmdKey, $allowedCommands)) {
    echo "<h1>Available Commands</h1><ul>";
    foreach ($allowedCommands as $key => $info) {
        echo "<li><a href='?key=$secret&cmd=$key'>$key</a> (php artisan {$info[0]})</li>";
    }
    echo "</ul>";
    exit;
}

$command = $allowedCommands[$cmdKey];

try {
    echo "<h1>Running: php artisan {$command[0]}</h1>";
    $exitCode = Artisan::call($command[0], $command[1]);
    echo "<pre>" . Artisan::output() . "</pre>";
    echo "<p><strong>Exit Code: $exitCode</strong></p>";
    echo "<p><a href='?key=$secret'>Back to list</a></p>";
} catch (\Exception $e) {
    echo "<h1>Error</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
