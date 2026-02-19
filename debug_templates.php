<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WhatsappTemplate;
use App\Validators\TemplateValidator;

$validator = new TemplateValidator();
foreach (WhatsappTemplate::all() as $template) {
    $result = $validator->validate($template);
    echo "Template: {$template->name} (ID: {$template->id}) - Category: {$template->category}\n";
    echo "Score: {$template->readiness_score}\n";
    if (!empty($template->validation_results)) {
        echo "Errors: " . json_encode($template->validation_results) . "\n";
    }
    echo "-------------------\n";
}
