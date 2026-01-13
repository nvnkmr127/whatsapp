<?php

namespace App\Providers;

use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Using base_path to ensure correct path resolution
        $this->mergeConfigFrom(
            base_path('config/whatsapp.php'),
            'whatsapp'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/whatsapp.php' => config_path('whatsapp.php'),
            ], 'whatsapp-config');
        }

        // Register failed job handler
        Queue::failing(function ($event) {
            if ($event->job->resolveName() === SendWhatsAppMessage::class) {
                whatsapp_log(
                    t('whatsapp_message_job_failed'),
                    'error',
                    [
                        'exception' => $event->exception->getMessage(),
                        'job'       => $event->job->payload(),
                    ],
                    $event->exception
                );
            }
        });
    }
}
