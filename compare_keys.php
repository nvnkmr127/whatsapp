<?php
$k25 = json_decode(file_get_contents('temp_viewed_25.json'), true); // we need to clean first, wait, let's clean
function get_key_from_file($file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $jsonLines = [];
    $inJson = false;
    foreach ($lines as $line) {
        $cleanLine = preg_replace('/^\s*\d+:\s*/', '', $line);
        $cleanLine = trim($cleanLine);
        if ($cleanLine === '{') $inJson = true;
        if ($inJson) $jsonLines[] = $cleanLine;
        if ($cleanLine === '}' && $inJson) $inJson = false;
    }
    $json = json_decode(implode("\n", $jsonLines), true);
    return $json['private_key'] ?? null;
}

$key25 = get_key_from_file('temp_viewed_25.json');
$key353 = get_key_from_file('temp_viewed_353.json');
$key695 = get_key_from_file('temp_viewed_695.json');

echo "Len 25: " . strlen($key25) . PHP_EOL;
echo "Len 353: " . strlen($key353) . PHP_EOL;
echo "Len 695: " . strlen($key695) . PHP_EOL;

if ($key25 === $key353) {
    echo "25 and 353 are identical" . PHP_EOL;
} else {
    echo "25 and 353 are DIFFERENT" . PHP_EOL;
}
if ($key353 === $key695) {
    echo "353 and 695 are identical" . PHP_EOL;
} else {
    echo "353 and 695 are DIFFERENT" . PHP_EOL;
}
