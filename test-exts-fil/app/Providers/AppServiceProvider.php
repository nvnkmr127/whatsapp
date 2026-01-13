<?php

namespace App\Providers;

use App\Services\MailService;
use App\Services\PusherService;
use App\Settings\PusherSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Spatie\LaravelSettings\Events\SettingsSaved;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(MailService::class);
        $this->app->singleton(PusherService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureMailService();
        $this->registerLivewireMacros();
        $this->configurePusher();
        $this->registerSettingsListeners();
        $this->configureTimezoneAndDateFormats();
        $this->configureBroadcasting();
    }

    /**
     * Configure mail service settings
     */
    private function configureMailService(): void
    {
        app(MailService::class)->setMailConfig();
    }

    /**
     * Register Livewire component macros
     */
    private function registerLivewireMacros(): void
    {
        Component::macro('notify', function ($message, $isAfterRedirect = false) {
            $payload = [
                'message' => $message['message'] ?? '',
                'type'    => $message['type']    ?? 'info',
            ];

            if ($isAfterRedirect) {
                session()->flash('notification', $payload);
            } else {
                $this->dispatch('notify', $payload);
            }
        });
    }

    /**
     * Configure Pusher settings
     */
    private function configurePusher(): void
    {
        try {
            $pusherSettings = app(PusherSettings::class);
            if (empty($pusherSettings->app_id)) {
                config(['broadcasting.connections.pusher' => $pusherSettings->toArray()]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load Pusher settings: ' . $e->getMessage());
        }
    }

    /**
     * Register settings changed listeners
     */
    private function registerSettingsListeners(): void
    {
        Event::listen(SettingsSaved::class, function (SettingsSaved $event) {
            $group = $event->settings::group();
            Cache::forget("settings.{$group}");
        });
    }

    /**
     * Configure timezone and date formats
     */
    private function configureTimezoneAndDateFormats(): void
    {
        // Set timezone
        $timezone = $this->getValidatedTimezone();
        Config::set('app.timezone', $timezone);
        date_default_timezone_set($timezone);

        // Get date and time formats
        $dateFormat        = get_setting('general.date_format', 'Y-m-d');
        $timeFormatSetting = get_setting('general.time_format', '24');
        $timeFormat        = $timeFormatSetting === '12' ? 'h:i A' : 'H:i';

        Config::set('app.date_format', $dateFormat);
        Config::set('app.time_format', $timeFormat);

        // Share with all views but only evaluate once
        View::share('dateTimeSettings', [
            'timezone'   => $timezone,
            'dateFormat' => $dateFormat,
            'timeFormat' => $timeFormat,
            'is24Hour'   => $timeFormatSetting === '24',
        ]);
    }

    /**
     * Get and validate timezone setting
     */
    private function getValidatedTimezone(): string
    {
        $timezone = Auth::check() && Auth::user()->timezone
            ? Auth::user()->timezone
            : get_setting('general.timezone', config('app.timezone'));

        return in_array($timezone, timezone_identifiers_list()) ? $timezone : 'UTC';
    }

    /**
     * Configure broadcasting routes
     */
    private function configureBroadcasting(): void
    {
        if (get_setting('pusher.enabled', false)) {
            Broadcast::routes(['middleware' => ['web', 'auth']]);
        }
    }
}
