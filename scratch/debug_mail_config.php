<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Default Mailer: " . config('mail.default') . "\n";
echo "SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
echo "SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Transactional Host: " . config('mail.mailers.transactional.host') . "\n";
echo "Transactional Port: " . config('mail.mailers.transactional.port') . "\n";
echo "Transactional Transport: " . config('mail.mailers.transactional.transport') . "\n";
