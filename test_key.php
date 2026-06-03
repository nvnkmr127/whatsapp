<?php
$config_path = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
putenv("OPENSSL_CONF=$config_path");

$config = json_decode(file_get_contents('storage/app/firebase-admin-sdk.json'), true);
$key = $config['private_key'];

echo "--- KEY START ---" . PHP_EOL;
echo $key . PHP_EOL;
echo "--- KEY END ---" . PHP_EOL;

$res = openssl_pkey_get_private($key);
if ($res === false) {
    echo "Failed to load key: " . openssl_error_string() . PHP_EOL;
} else {
    echo "Key loaded successfully!" . PHP_EOL;
}


