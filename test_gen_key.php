<?php
$config_path = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
putenv("OPENSSL_CONF=$config_path");

$res = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
    "config" => $config_path,
]);

if ($res === false) {
    echo "openssl_pkey_new failed. Errors:" . PHP_EOL;
    while ($err = openssl_error_string()) {
        echo " - " . $err . PHP_EOL;
    }
    exit;
}

openssl_pkey_export($res, $privKey, null, ["config" => $config_path]);
echo "Generated key:" . PHP_EOL;
echo substr($privKey, 0, 100) . "..." . PHP_EOL;

$res2 = openssl_pkey_get_private($privKey);
if ($res2 === false) {
    echo "Failed to load generated key: " . openssl_error_string() . PHP_EOL;
} else {
    echo "Generated key loaded successfully!" . PHP_EOL;
}

