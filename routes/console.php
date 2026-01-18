<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('whatsapp:sync-templates')->daily()->at('03:00');
Schedule::command('chats:process-status-rules')->hourly();
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();


