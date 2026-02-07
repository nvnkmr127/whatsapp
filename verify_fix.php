<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Traits\WhatsApp;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class WhatsAppTester
{
    use WhatsApp;

    public $team;

    public function __construct()
    {
        // Mock team with necessary attributes
        $this->team = new \stdClass();
        $this->team->id = 1;
        $this->team->whatsapp_access_token = 'mock_token';
        $this->team->whatsapp_business_account_id = 'mock_waba_id';
    }

    public function testLoadTemplates()
    {
        return $this->loadTemplatesFromWhatsApp();
    }
}

// Mock the Http facade to return the specific error
Http::fake([
    '*' => Http::response([
        'error' => [
            'message' => '(#100) The App_id in the input_token did not match the Viewing App',
            'type' => 'OAuthException',
            'code' => 100,
            'fbtrace_id' => 'AkFanpyMyGb7kXpe_8jebUZ'
        ]
    ], 400)
]);

try {
    $tester = new WhatsAppTester();
    echo "Testing loadTemplatesFromWhatsApp with mismatched token error...\n";
    $result = $tester->testLoadTemplates();

    // If it returns array (it shouldn't, it should throw exception based on my change)
    if (isset($result['status'])) {
        echo "Result Status: " . ($result['status'] ? 'True' : 'False') . "\n";
        echo "Result Message: " . $result['message'] . "\n";
    }

} catch (\Exception $e) {
    echo "\nCAUGHT EXCEPTION:\n";
    echo $e->getMessage() . "\n";

    if (str_contains($e->getMessage(), 'Configuration Error: The Access Token belongs to a different App ID')) {
        echo "\nSUCCESS: The specific Configuration Error was caught!\n";
    } else {
        echo "\nFAILURE: Caught generic exception instead of specific one.\n";
    }
}
