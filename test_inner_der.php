<?php
$config = json_decode(file_get_contents('storage/app/firebase-admin-sdk.json'), true);
$key = $config['private_key'];
$base64 = str_replace(["-----BEGIN PRIVATE KEY-----", "-----END PRIVATE KEY-----", "\r", "\n", " ", "\t"], "", $key);
$der = base64_decode($base64);

// The OCTET STRING starts at byte 22. Its header length is 4.
// So the actual inner RSA private key DER starts at byte 26.
$innerDer = substr($der, 26);
file_put_contents('inner_key.der', $innerDer);

$output = [];
$retval = 0;
exec('C:\xampp\apache\bin\openssl.exe asn1parse -inform DER -in inner_key.der 2>&1', $output, $retval);

echo "Retval: " . $retval . PHP_EOL;
echo "Output:" . PHP_EOL;
echo implode(PHP_EOL, $output) . PHP_EOL;

unlink('inner_key.der');
