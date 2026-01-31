<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Contact;
use App\Models\Team;
use App\Services\CallEligibilityService;
use Illuminate\Support\Facades\Auth;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $contact = Contact::latest()->first();
    $team = Team::latest()->first();

    if (!$contact) {
        die("No contacts found in database.\n");
    }
    if (!$team) {
        die("No teams found in database.\n");
    }

    Auth::loginUsingId($team->user_id);

    echo "Checking eligibility for contact: " . $contact->id . " on team: " . $team->id . "\n";

    $service = new CallEligibilityService($team);
    $eligibility = $service->checkEligibility($contact, 'user_initiated', [
        'trigger_source' => 'in_app_action'
    ]);

    print_r($eligibility);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
