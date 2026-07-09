<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes();

try {
    $c3 = new \App\Models\Conversation();
    $c3->setRawAttributes(['id' => 1, 'team_id' => 1], true);
    echo "Metadata value raw: " . json_encode($c3->metadata) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
