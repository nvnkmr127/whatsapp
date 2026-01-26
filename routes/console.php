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
Schedule::command('automation:resume')->everyMinute();

// Security & Maintenance
Schedule::command('audit:prune --days=90')->monthly()->at('01:00');

// Ecommerce Integration Health Checks
Schedule::job(new \App\Jobs\CheckIntegrationHealth)->everySixHours();

// Subscription Trial Expiry Checks
Schedule::job(new \App\Jobs\CheckTrialExpiry)->daily();

// Queue Worker for Background Jobs (runs every minute, keeps running for 55s)
// Changed from --stop-when-empty to --max-time=55 to prevent exit when queue is empty, reducing latency.
Schedule::command('queue:work --queue=broadcasts,messages,webhooks,default --max-time=55 --tries=3 --timeout=90 --sleep=2')
    ->everyMinute()
    ->withoutOverlapping();

// Broadcast Event Consumer (Polling loop that runs for 55s then exits, restarted by Cron)
// This replaces the need for a separate Supervisor process
Schedule::command('broadcast:consume --seconds=55')
    ->everyMinute()
    ->withoutOverlapping();



