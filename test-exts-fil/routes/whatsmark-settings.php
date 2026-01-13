<?php

/*
Project         :   WhatsApp Marketing & Automation Platform with Bots, Chats, Bulk Sender & AI
@package        :   Laravel
Laravel Version :   11.41.3
PHP Version     :   8.2.18
Created Date    :   14-01-2025
Copyright       :   Corbital Technologies LLP
Author          :   CORBITALTECH™
Author URL      :   https://codecanyon.net/user/corbitaltech
Support         :   contact@corbitaltech.dev
License         :   Licensed under Codecanyon Licence
*/

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SanitizeInputs;
use Illuminate\Support\Facades\Route;

Route::middleware([AdminMiddleware::class, SanitizeInputs::class])->group(function () {
    // Define settings routes dynamically
    $settings = [
        'whatsapp-auto-lead'      => App\Livewire\Admin\Settings\Whatsmark\WhatsappAutoLeadSettings::class,
        'stop-bot'                => App\Livewire\Admin\Settings\Whatsmark\StopBotSettings::class,
        'web-hooks'               => App\Livewire\Admin\Settings\Whatsmark\WebHooksSettings::class,
        'support-agent'           => App\Livewire\Admin\Settings\Whatsmark\SupportAgentSettings::class,
        'notification-sound'      => App\Livewire\Admin\Settings\Whatsmark\NotificationSoundSettings::class,
        'ai-integration'          => App\Livewire\Admin\Settings\Whatsmark\AiIntegrationSettings::class,
        'auto-clear-chat-history' => App\Livewire\Admin\Settings\Whatsmark\AutoClearChatHistorySettings::class,
    ];

    foreach ($settings as $prefix => $component) {
        Route::get("/settings/{$prefix}", $component)->name("{$prefix}.settings.view");
    }
});
