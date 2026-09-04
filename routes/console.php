<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WhatsApp Health Monitoring
Schedule::command('whatsapp:calculate-health-scores')->everyThirtyMinutes();
Schedule::command('whatsapp:prune-health-snapshots')->weeklyOn(0, '03:00');

// WhatsApp Monitoring
Schedule::command('whatsapp:validate-tokens')->daily()->at('02:00');
Schedule::command('whatsapp:monitor-phones')->everySixHours();
Schedule::command('whatsapp:check-setup-health')->everySixHours();

// Launch due scheduled campaigns (PrepareCampaignJob leaves them in 'scheduled')
Schedule::command('campaign:process')->everyMinute()->withoutOverlapping();

// Abandoned cart engine: expiry, reminders, cart_abandoned automation trigger
Schedule::command('commerce:process-carts')->everyFifteenMinutes()->withoutOverlapping();

// Existing schedules
Schedule::command('whatsapp:sync-templates')->daily()->at('03:00');
Schedule::command('chats:process-status-rules')->hourly();
Schedule::command('automation:resume')->everyMinute()->withoutOverlapping();
Schedule::command('app:send-daily-reports')->dailyAt('23:00')->timezone('Asia/Kolkata');
Schedule::command('reports:send')->dailyAt('08:00')->timezone('Asia/Kolkata');

// Security & Maintenance
Schedule::command('audit:prune --days=90')->monthly()->at('01:00');

// Ecommerce Integration Health Checks
Schedule::job(new \App\Jobs\CheckIntegrationHealth)->everySixHours();

// Subscription Trial Expiry Checks
Schedule::job(new \App\Jobs\CheckTrialExpiry)->daily();

// Fallback queue worker for hosts WITHOUT Supervisor (e.g. shared/cPanel).
// Runs every minute, works for ~55s, then exits and is restarted by cron.
// Covers EVERY queue jobs are dispatched onto so nothing is silently orphaned
// (priority order: inbound webhooks first, then chat, then everything else).
// NOTE: if you run Supervisor (supervisor-whatsapp-workers.conf), disable this
// line — those workers already cover all queues with per-queue tuning, and
// running both just adds a redundant worker.
Schedule::command('queue:work --queue=high,webhooks,messages,push_notifications,notifications,broadcasts,campaigns,automations,workflows,ai_processing,default --max-time=55 --tries=3 --timeout=600 --sleep=2')
    ->everyMinute()
    ->withoutOverlapping();

// Broadcast Event Consumer (Polling loop that runs for 55s then exits, restarted by Cron)
// This replaces the need for a separate Supervisor process
Schedule::command('broadcast:consume --count=500 --seconds=55')
    ->everyMinute()
    ->withoutOverlapping();

// Compliance Audits
Schedule::command('whatsapp:audit-compliance')->weekly()->sundays()->at('04:00');

// CRM Automation (Advanced Triggers T5 & T6)
Schedule::command('automations:check-inactivity')->everyFiveMinutes();
Schedule::command('automations:check-birthdays')->dailyAt('09:00');

// Prune old processed broadcast events older than 3 days
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('broadcast_events')
        ->where('status', 'processed')
        ->where('updated_at', '<', now()->subDays(3))
        ->delete();
})->dailyAt('03:15');

// Prune failed jobs older than 30 days
Schedule::command('queue:prune-failed --hours=720')->dailyAt('03:30');

Schedule::job(new \App\Jobs\CheckOnboardingInactivityJob)->everyTenMinutes();
