<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes();
\Illuminate\Database\Eloquent\Model::preventLazyLoading();

try {
    $c = new \App\Models\Conversation();
    $c->setRawAttributes(['id' => 1, 'team_id' => 1], true);
    
    // Test if toArray() throws on a missing cast
    $array = $c->toArray();
    echo "toArray() works without metadata. Keys: " . implode(', ', array_keys($array)) . "\n";
    
} catch (\Exception $e) {
    echo "Error on toArray: " . $e->getMessage() . "\n";
}

try {
    $c = new \App\Models\Conversation();
    $c->setRawAttributes(['id' => 1, 'team_id' => 1], true);
    // Explicit access
    $x = $c->metadata;
} catch (\Exception $e) {
    echo "Error on explicit access: " . $e->getMessage() . "\n";
}
