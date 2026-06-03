<?php
$logPath = 'C:\\Users\\madhu\\.gemini\\antigravity-ide\\brain\\45dec427-c216-40d8-aaa1-8c6b46065605\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logPath)) {
    die("Log file not found at: $logPath");
}

$handle = fopen($logPath, "r");
$lineNum = 0;
while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (str_contains($line, 'firebase-admin-sdk.json') && (str_contains($line, 'private_key') || str_contains($line, 'write_to_file'))) {
        echo "Line $lineNum matches!" . PHP_EOL;
        // Let's decode the JSON object on this line
        $data = json_decode($line, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Type: " . ($data['type'] ?? 'N/A') . PHP_EOL;
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $call) {
                    echo "Tool Call Name: " . ($call['name'] ?? 'N/A') . PHP_EOL;
                    if (isset($call['argumentsJson'])) {
                        $args = json_decode($call['argumentsJson'], true);
                        if (isset($args['CodeContent'])) {
                            echo "Found CodeContent! Length: " . strlen($args['CodeContent']) . PHP_EOL;
                            // Print first 200 chars
                            echo substr($args['CodeContent'], 0, 200) . "..." . PHP_EOL;
                            // Check if it's the sdk config
                            if (str_contains($args['CodeContent'], 'private_key')) {
                                echo "This is a write to firebase-admin-sdk.json! Saving to temp_sdk_found.json" . PHP_EOL;
                                file_put_contents("temp_sdk_found_$lineNum.json", $args['CodeContent']);
                            }
                        }
                    }
                }
            }
        } else {
            echo "Failed to parse JSON on line $lineNum" . PHP_EOL;
        }
        echo "------------------------------------------------" . PHP_EOL;
    }
}
fclose($handle);
