<?php

use App\Models\Contact;
use App\Models\Message;
use App\Models\Team;
use App\Services\AutomationService;
use App\Models\Automation;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Setup Test Data for Team 1
$teamId = 1;
$team = Team::find($teamId);
if (!$team) {
    echo "Team 1 not found\n";
    exit;
}

$phoneNumber = '1234567890';
$contact = Contact::firstOrCreate([
    'team_id' => $teamId,
    'phone_number' => $phoneNumber
], [
    'name' => 'Test User'
]);

// Ensure bot is not paused
$contact->update(['is_bot_paused' => false, 'assigned_to' => null]);

$triggerWord = 'hello';

echo "Testing Trigger Word: '$triggerWord' for Team $teamId\n";

$svc = app(AutomationService::class);
$result = $svc->checkTriggers($contact, $triggerWord);

if ($result) {
    echo "SUCCESS: Trigger matched and automation started!\n";
    $run = \App\Models\AutomationRun::where('contact_id', $contact->id)->latest()->first();
    echo "Run ID: " . ($run->id ?? 'N/A') . " Status: " . ($run->status ?? 'N/A') . "\n";
} else {
    echo "FAILURE: Trigger NOT matched.\n";
    
    // Debug: Why it failed?
    $handoff = new \App\Services\BotHandoffService();
    if (!$handoff->shouldProcess($contact)) {
        echo "Reason: Handoff blocked the process.\n";
    } else {
        $automations = Automation::where('team_id', $teamId)
            ->where('is_active', true)
            ->whereIn('trigger_type', ['keyword', 'multi'])
            ->get();
        echo "Active Automations Count: " . $automations->count() . "\n";
        foreach ($automations as $a) {
            echo "Auto #{$a->id} (Type: {$a->trigger_type}): " . json_encode($a->trigger_config) . "\n";
        }
    }
}
