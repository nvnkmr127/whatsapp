<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$team = App\Models\Team::find(5);
if (!$team) {
    echo "Team 5 not found\n";
    exit;
}
echo "Setup State: " . ($team->whatsapp_setup_state?->value ?? 'null') . "\n";
$engine = new App\Services\WhatsAppVerificationEngine($team);
$results = $engine->verify();
echo "Verification result:\n";
print_r($results);
