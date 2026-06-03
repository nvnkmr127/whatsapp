<?php
$config_path = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
putenv("OPENSSL_CONF=$config_path");

require __DIR__ . '/vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;

$credentialsPath = 'storage/app/firebase-admin-sdk.json';

try {
    $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
    $creds = new ServiceAccountCredentials($scopes, $credentialsPath);
    $authToken = $creds->fetchAuthToken();
    echo "Access Token: " . ($authToken['access_token'] ?? 'NONE') . PHP_EOL;
} catch (\Exception $e) {
    echo "Error fetching token: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}

