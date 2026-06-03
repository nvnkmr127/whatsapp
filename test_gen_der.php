<?php
$config_path = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
$res = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
    "config" => $config_path,
]);
openssl_pkey_export($res, $privKey, null, ["config" => $config_path]);
$base64 = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\t"], "", $privKey);
$der = base64_decode($base64);
file_put_contents('gen_key.der', $der);

$output = [];
$retval = 0;
exec('C:\xampp\apache\bin\openssl.exe asn1parse -inform DER -in gen_key.der 2>&1', $output, $retval);

echo "Retval: " . $retval . PHP_EOL;
echo "Output:" . PHP_EOL;
echo implode(PHP_EOL, $output) . PHP_EOL;

unlink('gen_key.der');
