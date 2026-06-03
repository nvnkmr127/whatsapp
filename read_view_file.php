<?php
$logPath = 'C:\\Users\\madhu\\.gemini\\antigravity-ide\\brain\\45dec427-c216-40d8-aaa1-8c6b46065605\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("Log file not found at: $logPath");
}

$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (str_contains($line, 'firebase-admin-sdk.json') && str_contains($line, 'VIEW_FILE')) {
        $data = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Line $lineNum matches VIEW_FILE!" . PHP_EOL;
            echo "Status: " . ($data['status'] ?? 'N/A') . PHP_EOL;
            if (isset($data['content'])) {
                echo "Found content in VIEW_FILE! Length: " . strlen($data['content']) . PHP_EOL;
                // If it looks like JSON containing private_key, save it
                if (str_contains($data['content'], 'private_key')) {
                    echo "Saving viewed content to temp_viewed_$lineNum.json" . PHP_EOL;
                    file_put_contents("temp_viewed_$lineNum.json", $data['content']);
                }
            }
        }
    }
}
fclose($handle);
