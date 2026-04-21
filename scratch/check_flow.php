<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = '981322974392021';
$flow = \App\Models\WhatsAppFlow::where('flow_id', $id)->first();

if ($flow) {
    echo "Found Flow:\n";
    echo "ID: " . $flow->id . "\n";
    echo "Meta Flow ID: " . $flow->flow_id . "\n";
    echo "Team ID: " . $flow->team_id . "\n";
    echo "Name: " . $flow->name . "\n";
    
    $team = $flow->team;
    echo "Team WABA ID: " . $team->whatsapp_business_account_id . "\n";
    
    $resolver = new \App\Core\WhatsApp\CredentialResolver();
    $creds = $resolver->resolve($team);
    echo "Resolved WABA ID: " . $creds['waba_id'] . "\n";
} else {
    echo "Flow with Meta ID {$id} NOT FOUND in database.\n";
}
