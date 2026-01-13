<?php

namespace App\Providers;

use App\Services\EnvWatcher;
use Illuminate\Support\ServiceProvider;

class EnvWatcherServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EnvWatcher::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->make(EnvWatcher::class)->checkForChanges();
    }
}
