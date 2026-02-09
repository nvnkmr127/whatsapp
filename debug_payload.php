<?php

use App\Models\WebhookPayload;
use App\Services\WebhookMappingService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payloadId = 268;
$payload = WebhookPayload::with('source')->find($payloadId);

if (!$payload) {
    echo "Payload $payloadId not found.\n";
    exit(1);
}

echo "Payload ID: {$payload->id}\n";
echo "Event Type: {$payload->event_type}\n";
echo "Source ID: {$payload->webhook_source_id}\n";
echo "Source Name: {$payload->source->name}\n";
echo "\n--- Payload Content ---\n";
echo json_encode($payload->payload, JSON_PRETTY_PRINT) . "\n";

echo "\n--- Source Field Mappings ---\n";
$mappings = $payload->source->field_mappings;
echo json_encode($mappings, JSON_PRETTY_PRINT) . "\n";

echo "\n--- Mapping Test ---\n";
$service = new WebhookMappingService();
$eventType = $payload->event_type;
$rules = $payload->source->getFieldMapping($eventType);

echo "Rules for event '$eventType':\n";
echo json_encode($rules, JSON_PRETTY_PRINT) . "\n";

$mapped = $service->mapFields($payload->payload, $rules);
echo "Mapped Result:\n";
echo json_encode($mapped, JSON_PRETTY_PRINT) . "\n";
