<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WhatsApp Health Monitoring
Schedule::command('whatsapp:calculate-health-scores')->everyThirtyMinutes();

// WhatsApp Monitoring
Schedule::command('whatsapp:validate-tokens')->daily()->at('02:00');
Schedule::command('whatsapp:monitor-phones')->everySixHours();
Schedule::command('whatsapp:detect-stuck-setups --fix')->hourly();
Schedule::command('whatsapp:check-setup-health')->everySixHours();

// Existing schedules
Schedule::command('whatsapp:sync-templates')->daily()->at('03:00');
Schedule::command('chats:process-status-rules')->hourly();
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

