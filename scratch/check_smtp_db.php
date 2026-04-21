<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$configs = \App\Models\SmtpConfig::all();
echo "Found " . $configs->count() . " SMTP configs:\n";
foreach ($configs as $cfg) {
    echo "- Name: {$cfg->name}, Host: {$cfg->host}, Port: {$cfg->port}, Active: " . ($cfg->is_active ? 'Yes' : 'No') . "\n";
}
