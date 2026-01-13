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
    $settings = [
        'email' => App\Livewire\Admin\Settings\System\EmailSettings::class,
        'webhook' => App\Livewire\Admin\Settings\System\WebhookSettingsManager::class,
        'general' => App\Livewire\Admin\Settings\System\GeneralSettings::class,
        're-captcha' => App\Livewire\Admin\Settings\System\ReCaptchaSettings::class,
        'cron-job' => App\Livewire\Admin\Settings\System\CronJobSettings::class,
        'system-update' => App\Livewire\Admin\Settings\System\SystemUpdateSettings::class,
        'system-information' => App\Livewire\Admin\Settings\System\SystemInformationSettings::class,
        'cache-management' => App\Livewire\Admin\Settings\System\CacheManagementSettings::class,
        'announcement' => App\Livewire\Admin\Settings\System\AnnouncementSettings::class,
        'seo' => App\Livewire\Admin\Settings\System\SeoSettings::class,
        'pusher' => App\Livewire\Admin\Settings\System\PusherSettings::class,
        'api-management' => App\Livewire\Admin\Settings\System\ManageApiTokens::class,
    ];

    foreach ($settings as $prefix => $component) {
        Route::get("/{$prefix}", $component)->name("{$prefix}.settings.view");
    }
});
