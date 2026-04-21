<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = '981322974392021';
$team = \App\Models\Team::where('whatsapp_business_account_id', $id)->first();

if ($team) {
    echo "Found Team with this WABA ID:\n";
    echo "Team ID: " . $team->id . "\n";
    echo "Team Name: " . $team->name . "\n";
} else {
    echo "WABA ID {$id} NOT FOUND in teams table.\n";
}
