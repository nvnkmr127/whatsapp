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
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            // Limit by Team ID if authenticated, otherwise IP
            $key = $request->user()?->current_team_id ?: $request->ip();
            // 600 requests per minute per Team (10 req/sec)
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($key);
        });

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\AutomationTriggerListener::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\SendOutboundWebhook::class
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
                return $user->hasTeamPermission($user->currentTeam, $permission) || $user->ownsTeam($user->currentTeam);
            });
        }
    }
}
