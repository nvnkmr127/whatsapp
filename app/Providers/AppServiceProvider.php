<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', \App\Http\View\Composers\GlobalSettingsComposer::class);

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            // Limit by Team ID if authenticated, otherwise IP
            $key = $request->user()?->current_team_id ?: $request->ip();
            // 600 requests per minute per Team (10 req/sec)
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($key);
        });

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\LogSuccessfulLogin::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            \App\Listeners\LogSuccessfulLogout::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\AutomationTriggerListener::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\UpdateContactStateOnMessageReceived::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\SendOutboundWebhook::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\OrderStatusUpdated::class,
            \App\Listeners\SendOrderLifecycleNotification::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ContactLifecycleChanged::class,
            \App\Listeners\LogContactEvents::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ContactOptedOut::class,
            \App\Listeners\LogContactEvents::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\WhatsAppTokenExpiringSoon::class,
            \App\Listeners\NotifyAdminsOfTokenExpiry::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UsageThresholdReached::class,
            \App\Listeners\NotifyTeamOfBillingAlert::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageSent::class,
            \App\Listeners\SendMessageSentWebhook::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageStatusUpdated::class,
            \App\Listeners\SendMessageStatusWebhook::class
        );

        // Call Billing - Process charges when calls end
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\CallEnded::class,
            \App\Listeners\ProcessCallBilling::class
        );

        \Illuminate\Support\Facades\Event::listen(
            [
                \App\Events\CallEnded::class,
                \App\Events\CallMissed::class,
                \App\Events\CallRejected::class,
                \App\Events\CallFailed::class,
            ],
            \App\Listeners\SyncCallToInboxListener::class
        );

        // Catch-all for Domain Events (Signal Sourcing)
        \Illuminate\Support\Facades\Event::listen(
            'App\Events\*',
            \App\Listeners\PersistDomainEvents::class
        );

        $permissions = [
            'manage-settings',
            'manage-billing',
            'chat-access',
            'manage-contacts',
            'manage-campaigns',
            'manage-templates',
        ];

        foreach ($permissions as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, function ($user) use ($permission) {
                // Global Override for Super Admin
                if ($user->isSuperAdmin()) {
                    return true;
                }

                return $user->hasTeamPermission($user->currentTeam, $permission) || $user->ownsTeam($user->currentTeam);
            });
        }
    }
}
