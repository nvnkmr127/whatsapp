<?php

namespace App\Providers;

use App\Services\TraceContext;
use Illuminate\Support\Facades\Queue;
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
        $this->app->singleton(\App\Core\WhatsApp\WhatsAppClient::class);

        // ── Email Provisioning Manager ──
        $this->app->singleton(\App\Services\Email\EmailProviderManager::class, function ($app) {
            $drivers = [
                'smtp' => new \App\Services\Email\Drivers\SmtpDriver($app->make(\App\Services\Email\SmtpHealthService::class)),
            ];

            $resendKey = config('services.resend.api_key');
            if ($resendKey) {
                $drivers['resend'] = new \App\Services\Email\Drivers\ResendDriver(
                    $resendKey,
                    config('services.resend.from_address'),
                    config('services.resend.from_name')
                );
            }

            return new \App\Services\Email\EmailProviderManager($drivers);
        });

        // Update EmailDispatcher injection to match new single-dependency signature
        $this->app->singleton(\App\Services\Email\EmailDispatcher::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Safe Broadcasters (Pusher & Reverb) ─────────────────────────
        \Illuminate\Support\Facades\Broadcast::extend('reverb', function ($app, array $config) {
            $client = new \GuzzleHttp\Client(array_merge(
                $config['client_options'] ?? [],
                [
                    'http_errors' => false,
                ]
            ));

            $pusher = new \Pusher\Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options'] ?? [],
                $client
            );

            if ($config['log'] ?? false) {
                $pusher->setLogger($app->make(\Psr\Log\LoggerInterface::class));
            }

            return new \App\Broadcasting\SafePusherBroadcaster($pusher);
        });

        \Illuminate\Support\Facades\Broadcast::extend('pusher', function ($app, array $config) {
            $client = new \GuzzleHttp\Client(array_merge(
                $config['client_options'] ?? [],
                [
                    'http_errors' => false,
                ]
            ));

            $pusher = new \Pusher\Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options'] ?? [],
                $client
            );

            if ($config['log'] ?? false) {
                $pusher->setLogger($app->make(\Psr\Log\LoggerInterface::class));
            }

            return new \App\Broadcasting\SafePusherBroadcaster($pusher);
        });

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
            $key = $request->user()?->current_team_id ?: $request->ip();

            return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($key);
        });

        \Illuminate\Support\Facades\RateLimiter::for('mobile-auth', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip());
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
            [
                \App\Events\MessageReceived::class,
                \App\Events\CallOffered::class,
            ],
            \App\Listeners\SendPushNotificationListener::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\MessageReceived::class,
            \App\Listeners\AiCommerceListener::class
        );

        \Illuminate\Support\Facades\Event::listen(
            'App\Events\*',
            \App\Listeners\PersistDomainEvents::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\WhatsAppAccountRisk::class,
            \App\Listeners\MonitorAccountHealth::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UsageThresholdReached::class,
            \App\Listeners\NotifyTeamOfBillingAlert::class
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

                if (! $team) {
                    \Illuminate\Support\Facades\Log::warning("Gate check failed: User {$user->id} has no team association for permission {$permission}");

                    return false;
                }

                $ownsTeam = $user->ownsTeam($team);
                $hasPermission = $user->hasTeamPermission($team, $permission);

                if (! $ownsTeam && ! $hasPermission) {
                    \Illuminate\Support\Facades\Log::warning("Gate check failed: User {$user->id} does not have permission {$permission} on team {$team->id}. Owns team: ".($ownsTeam ? 'YES' : 'NO').', Has direct permission: '.($hasPermission ? 'YES' : 'NO'));

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
