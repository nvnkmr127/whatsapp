<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class WhatsAppRateLimitingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        RateLimiter::for('whatsapp-api', function ($job) {
            return [
                Limit::perMinute(250),
                Limit::perHour(3000)->by($job->campaign->id),
            ];
        });
    }
}
