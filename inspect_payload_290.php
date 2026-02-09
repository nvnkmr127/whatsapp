<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== INSPECTING PAYLOAD 290 ===\n\n";

$payload = \App\Models\WebhookPayload::find(290);

if (!$payload) {
    echo "❌ Payload 290 not found.\n";
    exit(1);
}

echo "Payload ID: {$payload->id}\n";
echo "Event Type: {$payload->event_type}\n";
echo "Status: {$payload->status}\n\n";

echo "=== RAW PAYLOAD ===\n";
echo json_encode($payload->payload, JSON_PRETTY_PRINT) . "\n\n";

echo "=== MAPPED DATA ===\n";
echo json_encode($payload->mapped_data, JSON_PRETTY_PRINT) . "\n\n";

echo "=== TESTING EXTRACTION ===\n";

$paths = [
    'data.client.name',
    'data.reference',
    'data.total',
    'data.url',
    'data.pdf',
    'data.client.phone',
];

foreach ($paths as $path) {
    $value = data_get($payload->payload, $path);
    $status = $value !== null ? '✓' : '✗';
    echo "{$status} {$path}: " . json_encode($value) . "\n";
}

echo "\n=== DIAGNOSIS ===\n";

if (empty($payload->mapped_data)) {
    echo "❌ mapped_data is EMPTY - field mappings didn't work!\n";
    echo "This means the field_mappings configuration doesn't match the payload structure.\n";
} else {
    echo "✓ mapped_data has values\n";
}

echo "\n";
