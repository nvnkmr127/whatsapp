<?php
putenv('OPENSSL_CONF=C:\xampp\php\extras\ssl\openssl.cnf');

$config = json_decode(file_get_contents('storage/app/firebase-admin-sdk.json'), true);
$key = $config['private_key'];

// Extract the base64 payload
$base64 = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\t"], "", $key);

// Re-wrap to 64 chars
$cleanKey = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($base64, 64, "\n") . "-----END PRIVATE KEY-----\n";

$decoded = base64_decode($base64, true);
if ($decoded === false) {
    echo "Base64 decode failed!" . PHP_EOL;
} else {
    echo "Base64 decode success! Size: " . strlen($decoded) . " bytes" . PHP_EOL;
}

$res = openssl_pkey_get_private($cleanKey);
if ($res === false) {
    echo "Cleaned key failed: " . openssl_error_string() . PHP_EOL;
} else {
    echo "Cleaned key SUCCESS!" . PHP_EOL;
}
