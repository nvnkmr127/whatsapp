<?php

use App\Models\Team;
use Illuminate\Support\Facades\Log;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$team = Team::orderBy('id', 'desc')->first(); // Assuming the latest team is the current one

if (!$team) {
    echo "No team found\n";
    exit;
}

echo "Team: " . $team->name . " (ID: " . $team->id . ")\n";
echo "WABA ID: " . $team->whatsapp_business_account_id . "\n";
echo "Phone ID: " . $team->whatsapp_phone_number_id . "\n";
echo "Setup State: " . $team->whatsapp_setup_state . "\n";
echo "Token Exists: " . ($team->whatsapp_access_token ? 'Yes' : 'No') . "\n";
echo "Token Expires At: " . $team->whatsapp_token_expires_at . "\n";

$engine = app(\App\Services\WhatsAppVerificationEngine::class)->setTeam($team);
$results = $engine->verify();

echo "\nVerification Results:\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
