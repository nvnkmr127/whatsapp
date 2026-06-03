<?php
$config = json_decode(file_get_contents('storage/app/firebase-admin-sdk.json'), true);
$key = $config['private_key'];
file_put_contents('temp_key.pem', $key);

$output = [];
$retval = 0;
exec('C:\xampp\apache\bin\openssl.exe pkey -in temp_key.pem -text -noout 2>&1', $output, $retval);

echo "Retval: " . $retval . PHP_EOL;
echo "Output:" . PHP_EOL;
echo implode(PHP_EOL, $output) . PHP_EOL;

unlink('temp_key.pem');
