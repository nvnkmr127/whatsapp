<?php
$config = json_decode(file_get_contents('storage/app/firebase-admin-sdk.json'), true);
$key = $config['private_key'];
echo 'Length: ' . strlen($key) . PHP_EOL;
echo 'Line breaks count: ' . substr_count($key, "\n") . PHP_EOL;
echo 'Literal slash-n count: ' . substr_count($key, "\\n") . PHP_EOL;
echo 'CR count: ' . substr_count($key, "\r") . PHP_EOL;

// Let's print the line count by splitting by newline
$lines = explode("\n", $key);
echo 'Lines after explode \\n: ' . count($lines) . PHP_EOL;
foreach ($lines as $i => $line) {
    echo "Line $i (len=" . strlen($line) . "): " . substr($line, 0, 15) . "..." . substr($line, -15) . PHP_EOL;
}
