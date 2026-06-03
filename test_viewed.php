<?php
$config_path = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
putenv("OPENSSL_CONF=$config_path");

$files = glob('temp_viewed_*.json');
foreach ($files as $file) {
    echo "Testing $file... " . PHP_EOL;
    $content = file_get_contents($file);
    
    $lines = explode("\n", $content);
    $jsonLines = [];
    $inJson = false;
    foreach ($lines as $line) {
        // Strip single line number prefix like "1: {" or "13: }"
        $cleanLine = preg_replace('/^\s*\d+:\s*/', '', $line);
        $cleanLine = trim($cleanLine);
        
        if ($cleanLine === '{') {
            $inJson = true;
        }
        if ($inJson) {
            $jsonLines[] = $cleanLine;
        }
        if ($cleanLine === '}' && $inJson) {
            $inJson = false;
        }
    }
    
    if (empty($jsonLines)) {
        echo "No JSON lines extracted." . PHP_EOL;
        continue;
    }
    
    $cleanContent = implode("\n", $jsonLines);
    $json = json_decode($cleanContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Failed to decode JSON: " . json_last_error_msg() . PHP_EOL;
        continue;
    }
    
    if (!isset($json['private_key'])) {
        echo "No private_key key in JSON." . PHP_EOL;
        continue;
    }
    
    $key = $json['private_key'];
    // In JSON, literal \n was written as \n, which json_decode parsed to a newline character.
    // Let's test the key.
    $res = openssl_pkey_get_private($key);
    if ($res === false) {
        echo "FAILED to load private key: " . openssl_error_string() . PHP_EOL;
    } else {
        echo "SUCCESS! Valid key found in $file!" . PHP_EOL;
        file_put_contents('storage/app/firebase-admin-sdk.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "Restored storage/app/firebase-admin-sdk.json successfully!" . PHP_EOL;
    }
}
