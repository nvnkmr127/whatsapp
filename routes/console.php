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

// Queue Worker for Background Jobs (runs every minute, stops when empty to release resources)
// Added 'broadcasts' queue to ensure campaign messages are sent
Schedule::command('queue:work --queue=broadcasts,messages,webhooks,default --stop-when-empty --tries=3 --timeout=90')
    ->everyMinute()
    ->withoutOverlapping();

// Broadcast Event Consumer (Polling loop that runs for 55s then exits, restarted by Cron)
// This replaces the need for a separate Supervisor process
Schedule::command('broadcast:consume --seconds=55')
    ->everyMinute()
    ->withoutOverlapping();

