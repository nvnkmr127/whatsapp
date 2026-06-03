<?php
$lines = explode("\n", file_get_contents('temp_viewed_25.json'));
for ($i = 0; $i < min(15, count($lines)); $i++) {
    echo "Line $i (len=" . strlen($lines[$i]) . "): [" . $lines[$i] . "]" . PHP_EOL;
}
