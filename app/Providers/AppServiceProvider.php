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
        // ── Entitlement stack – singletons so in-request cache is shared ──
        $this->app->singleton(\App\Services\OfferSettingsService::class);
        $this->app->singleton(\App\Services\OfferEligibilityService::class);
        $this->app->singleton(\App\Services\EntitlementService::class);
        $this->app->singleton(\App\Services\TrialOverrideService::class);
        $this->app->singleton(\App\Services\OutboundPreflightService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Model Observers ─────────────────────────────────────────────
        \App\Models\Team::observe(\App\Observers\TeamObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);

        \Illuminate\Support\Facades\View::composer('*', \App\Http\View\Composers\GlobalSettingsComposer::class);

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            // Limit by Team ID if authenticated, otherwise IP
            $key = $request->user()?->current_team_id ?: $request->ip();
            // 600 requests per minute per Team (10 req/sec)
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($key);
        });

        \Illuminate\Support\Facades\Event::listen(
            [
                \App\Events\ContactLifecycleChanged::class,
                \App\Events\ContactOptedOut::class,
            ],
            \App\Listeners\LogContactEvents::class
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

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\AutomationTriggerListener::class
        );

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

        \Illuminate\Support\Facades\Gate::define('viewLogViewer', function ($user = null) {
            // In production, you should restrict this to specific emails
            return true;
        });
    }
}
