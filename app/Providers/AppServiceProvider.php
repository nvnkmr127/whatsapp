<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use App\Services\TraceContext;

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
        $this->app->singleton(\App\Core\WhatsApp\WhatsAppClient::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Trace ID Propagation: Inject into all Job Payloads ──
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $traceId = TraceContext::getTraceId();
            if ($traceId) {
                return ['trace_id' => $traceId];
            }
            return [];
        });

        // ── Trace Context Restoration for Workers ──
        Queue::before(function (\Illuminate\Queue\Events\JobProcessing $event) {
            $payload = $event->job->payload();
            $traceId = $payload['trace_id'] ?? null;
            
            if ($traceId) {
                TraceContext::set($traceId);
            } else {
                TraceContext::ensureTraceId();
            }
        });

        // ── Model Observers ─────────────────────────────────────────────
        \App\Models\Team::observe(\App\Observers\TeamObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\OnboardingStatus::observe(\App\Observers\OnboardingObserver::class);

        // Contact Observers
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\Contact::observe(\App\Observers\WorkflowObserver::class);

        // Deal Observers
        \App\Models\Deal::observe(\App\Observers\DealObserver::class);

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
                \App\Events\ContactCreated::class,
            ],
            \App\Listeners\LogContactEvents::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\ContactCreated::class,
            \App\Listeners\TriggerWorkflowsOnContactCreated::class
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

        // Register Workflow Event Subscriber for advanced trigger events
        \Illuminate\Support\Facades\Event::subscribe(\App\Listeners\WorkflowEventSubscriber::class);

        $permissions = [
            'manage-settings',
            'manage-billing',
            'chat-access',
            'manage-contacts',
            'manage-campaigns',
            'manage-templates',
            'manage-workflows',
        ];

        foreach ($permissions as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, function ($user) use ($permission) {
                // Global Override for Super Admin
                if ($user->isSuperAdmin()) {
                    return true;
                }

                $team = $user->currentTeam ?? $user->allTeams()->first();

                if (!$team) {
                    \Illuminate\Support\Facades\Log::warning("Gate check failed: User {$user->id} has no team association for permission {$permission}");
                    return false;
                }

                $ownsTeam = $user->ownsTeam($team);
                $hasPermission = $user->hasTeamPermission($team, $permission);

                if (!$ownsTeam && !$hasPermission) {
                    \Illuminate\Support\Facades\Log::warning("Gate check failed: User {$user->id} does not have permission {$permission} on team {$team->id}. Owns team: " . ($ownsTeam ? 'YES' : 'NO') . ", Has direct permission: " . ($hasPermission ? 'YES' : 'NO'));
                    return false;
                }

                return true;
            });
        }

        \Illuminate\Support\Facades\Gate::define('viewLogViewer', function ($user = null) {
            // In production, you should restrict this to specific emails
            return true;
        });
    }
}
