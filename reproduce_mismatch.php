<?php

use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Configured App ID (from .env)
$configuredAppId = config('whatsapp.app_id');
$configuredAppSecret = config('whatsapp.app_secret');

echo 'Configured App ID: '.$configuredAppId."\n";
echo 'Configured App Secret (Partial): '.substr($configuredAppSecret, 0, 5)."...\n";

// Use a mock token that would belong to a DIFFERENT App ID
// In a real scenario, this comes from the user input
// For reproduction, we can use a dummy token or try to use the one from the DB if it exists
// But we know from previous steps the DB token might be empty or valid.
// Let's simluate the trait logic directly.

$mockToken = 'EAAB...'; // We need a real token structure or a way to trigger the specific error.
// Since we can't easily generate a valid token for another app without credentials,
// we will rely on the previous debug script's finding or just mock the *response* to test the exception handling proposed.

// Actually, better to test the *Logic* we plan to add.
// The error is: (#100) The App_id in the input_token did not match the Viewing App

echo "Simulating OAuth Exception 100 handling...\n";

$errorResponse = [
    'error' => [
        'message' => '(#100) The App_id in the input_token did not match the Viewing App',
        'type' => 'OAuthException',
        'code' => 100,
        'fbtrace_id' => 'AkFanpyMyGb7kXpe_8jebUZ',
    ],
];

// We want to verify if our proposed fix will catch this.
// PROPOSED FIX LOGIC:
try {
    // throw new \Exception(json_encode($errorResponse)); // This is what Http client throws? No, it returns response.

    // Simulating the check in Trait:
    $responseBody = json_encode($errorResponse);
    $responseJson = json_decode($responseBody, true);

    if (isset($responseJson['error']['code']) && $responseJson['error']['code'] == 100) {
        if (str_contains($responseJson['error']['message'], 'App_id in the input_token did not match')) {
            throw new \Exception('Configuration Mismatch: The provided Access Token belongs to a different App ID than the one configured in .env (FACEBOOK_APP_ID). Please regenerate the token for App ID: '.$configuredAppId);
        }
    }

    echo "Failed to catch error with proposed logic.\n";

} catch (\Exception $e) {
    echo 'Caught expected exception: '.$e->getMessage()."\n";
}
