<?php
$config = json_decode(file_get_contents('storage/app/firebase-admin-sdk.json'), true);
$key = $config['private_key'];
$base64 = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\t"], "", $key);
$der = base64_decode($base64);

$offset = 533;
echo "Bytes around offset $offset:" . PHP_EOL;
for ($i = -10; $i < 30; $i++) {
    $idx = $offset + $i;
    if ($idx >= 0 && $idx < strlen($der)) {
        printf("%4d (0x%03X): %02X\n", $idx, $idx, ord($der[$idx]));
    }
}
