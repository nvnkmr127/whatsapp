<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        // Fix permission issues by using a temp directory
        $path = '/tmp/laravel_storage_test';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        if (!file_exists($path . '/framework/views')) {
            mkdir($path . '/framework/views', 0777, true);
        }
        if (!file_exists($path . '/framework/cache')) {
            mkdir($path . '/framework/cache', 0777, true);
        }
        if (!file_exists($path . '/framework/sessions')) {
            mkdir($path . '/framework/sessions', 0777, true);
        }
        $app->useStoragePath($path);

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}
