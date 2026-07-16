<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

echo "Starting Cloudflare R2 Integration Test...\n";

$disk = Storage::disk('r2');
$filename = 'r2-test-' . Str::random(10) . '.txt';
$content = "This is a test file for Cloudflare R2 integration generated at " . now()->toDateTimeString();

try {
    // 1. Upload Test
    echo "1. Uploading file '{$filename}'... ";
    $disk->put($filename, $content);
    echo "✅ Success\n";

    // 2. Existence Test
    echo "2. Checking if file exists... ";
    if ($disk->exists($filename)) {
        echo "✅ Success\n";
    } else {
        echo "❌ Failed (File not found)\n";
        exit(1);
    }

    // 3. Read Test
    echo "3. Reading file content... ";
    $retrievedContent = $disk->get($filename);
    if ($retrievedContent === $content) {
        echo "✅ Success\n";
    } else {
        echo "❌ Failed (Content mismatch)\n";
        exit(1);
    }

    // 4. URL Generation Test
    echo "4. Generating URL... ";
    $url = $disk->url($filename);
    echo "✅ Success ({$url})\n";

    // 5. Delete Test
    echo "5. Deleting file... ";
    $disk->delete($filename);
    
    if (!$disk->exists($filename)) {
        echo "✅ Success\n";
    } else {
        echo "❌ Failed (File still exists)\n";
        exit(1);
    }

    echo "\n🎉 All tests passed successfully! R2 integration is fully working.\n";

} catch (\Exception $e) {
    echo "\n❌ Error occurred: " . $e->getMessage() . "\n";
    echo "Check your .env credentials and ensure your R2 bucket exists.\n";
}
