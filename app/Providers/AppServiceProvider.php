<?php

namespace App\Providers;

use App\Broadcasting\SafePusherBroadcaster;
use App\Core\WhatsApp\WhatsAppClient;
use App\Events\CallEnded;
use App\Events\CallFailed;
use App\Events\CallMissed;
use App\Events\CallOffered;
use App\Events\CallRejected;
use App\Events\ContactCreated;
use App\Events\ContactLifecycleChanged;
use App\Events\ContactOptedOut;
use App\Events\MessageReceived;
use App\Events\UsageThresholdReached;
use App\Events\WhatsAppAccountRisk;
use App\Http\View\Composers\GlobalSettingsComposer;
use App\Listeners\AiCommerceListener;
use App\Listeners\AutomationTriggerListener;
use App\Listeners\LogContactEvents;
use App\Listeners\MonitorAccountHealth;
use App\Listeners\NotifyTeamOfBillingAlert;
use App\Listeners\PersistDomainEvents;
use App\Listeners\SendPushNotificationListener;
use App\Listeners\SyncCallToInboxListener;
use App\Listeners\TriggerWorkflowsOnContactCreated;
use App\Listeners\WorkflowEventSubscriber;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\OnboardingStatus;
use App\Models\Team;
use App\Models\User;
use App\Observers\ContactObserver;
use App\Observers\DealObserver;
use App\Observers\OnboardingObserver;
use App\Observers\TeamObserver;
use App\Observers\UserObserver;
use App\Observers\WorkflowObserver;
use App\Services\EntitlementService;
use App\Services\OfferEligibilityService;
use App\Services\OfferSettingsService;
use App\Services\OutboundPreflightService;
use App\Services\TraceContext;
use App\Services\TrialOverrideService;
use GuzzleHttp\Client;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Pusher\Pusher;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Entitlement stack – scoped so in-request cache doesn't leak in Octane ──
        $this->app->scoped(OfferSettingsService::class);
        $this->app->scoped(OfferEligibilityService::class);
        $this->app->scoped(EntitlementService::class);
        $this->app->scoped(TrialOverrideService::class);
        $this->app->scoped(OutboundPreflightService::class);
        $this->app->scoped(WhatsAppClient::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Performance: Strict Mode ────────────────────────────────────
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->environment('testing')) {
            Model::preventAccessingMissingAttributes(false);
        }

        // ── Make silent data loss observable in production ──────────────
        // Outside production, writing an attribute missing from $fillable
        // throws. In production it is silently discarded, which is how the
        // signup form lost phone/company/utm_* and the campaign wizard lost
        // its header media filename — for months, with no signal at all.
        //
        // Throwing in production would turn old data loss into new 500s for
        // customers, so log it instead: same visibility, no blast radius.
        if ($this->app->isProduction()) {
            Model::preventSilentlyDiscardingAttributes();

            Model::handleDiscardedAttributeViolationUsing(function ($model, $keys) {
                Log::warning('Discarded unfillable attributes', [
                    'model' => $model::class,
                    'attributes' => $keys,
                ]);
            });
        }

        // ── System WhatsApp: DB settings override env defaults ──────────
        // Admins set these on /settings/system; env (WHATSAPP_SYSTEM_*) is the fallback.
        try {
            foreach (['system_waba_id', 'system_phone_number_id', 'system_access_token'] as $key) {
                $value = get_setting($key);
                if (! empty($value)) {
                    config(["whatsapp.$key" => $value]);
                }
            }
        } catch (\Throwable $e) {
            // Settings table not ready (e.g. during migration) — env defaults stand.
        }

        // ── Safe Broadcasters (Pusher & Reverb) ─────────────────────────
        Broadcast::extend('reverb', function ($app, array $config) {
            $client = new Client(array_merge(
                $config['client_options'] ?? [],
                [
                    'http_errors' => false,
                ]
            ));

            $pusher = new Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options'] ?? [],
                $client
            );

            if ($config['log'] ?? false) {
                $pusher->setLogger($app->make(LoggerInterface::class));
            }

            return new SafePusherBroadcaster($pusher);
        });

        Broadcast::extend('pusher', function ($app, array $config) {
            $client = new Client(array_merge(
                $config['client_options'] ?? [],
                [
                    'http_errors' => false,
                ]
            ));

            $pusher = new Pusher(
                $config['key'],
                $config['secret'],
                $config['app_id'],
                $config['options'] ?? [],
                $client
            );

            if ($config['log'] ?? false) {
                $pusher->setLogger($app->make(LoggerInterface::class));
            }

            return new SafePusherBroadcaster($pusher);
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
        Queue::before(function (JobProcessing $event) {
            $payload = $event->job->payload();
            $traceId = $payload['trace_id'] ?? null;

            if ($traceId) {
                TraceContext::set($traceId);
            } else {
                TraceContext::ensureTraceId();
            }
        });

        // ── Model Observers ─────────────────────────────────────────────
        Team::observe(TeamObserver::class);
        User::observe(UserObserver::class);
        OnboardingStatus::observe(OnboardingObserver::class);

        // Contact Observers
        Contact::observe(ContactObserver::class);
        Contact::observe(WorkflowObserver::class);

        // Deal Observers
        Deal::observe(DealObserver::class);

        View::composer('*', GlobalSettingsComposer::class);

        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->current_team_id ?: $request->ip();

            return Limit::perMinute(600)->by($key);
        });

        RateLimiter::for('mobile-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Event::listen(
            [
                ContactLifecycleChanged::class,
                ContactOptedOut::class,
                ContactCreated::class,
            ],
            LogContactEvents::class
        );

        Event::listen(
            ContactCreated::class,
            TriggerWorkflowsOnContactCreated::class
        );

        Event::listen(
            [
                CallEnded::class,
                CallMissed::class,
                CallRejected::class,
                CallFailed::class,
            ],
            SyncCallToInboxListener::class
        );

        Event::listen(
            MessageReceived::class,
            AutomationTriggerListener::class
        );

        Event::listen(
            CallOffered::class,
            SendPushNotificationListener::class
        );

        Event::listen(
            MessageReceived::class,
            AiCommerceListener::class
        );

        Event::listen(
            'App\Events\*',
            PersistDomainEvents::class
        );

        Event::listen(
            WhatsAppAccountRisk::class,
            MonitorAccountHealth::class
        );

        Event::listen(
            UsageThresholdReached::class,
            NotifyTeamOfBillingAlert::class
        );

        // Register Workflow Event Subscriber for advanced trigger events
        Event::subscribe(WorkflowEventSubscriber::class);

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
            Gate::define($permission, function ($user) use ($permission) {
                // Global Override for Super Admin
                if ($user->isSuperAdmin()) {
                    return true;
                }

                $team = $user->currentTeam ?? $user->allTeams()->first();

                if (! $team) {
                    Log::warning("Gate check failed: User {$user->id} has no team association for permission {$permission}");

                    return false;
                }

                $ownsTeam = $user->ownsTeam($team);
                $hasPermission = $user->hasTeamPermission($team, $permission);

                if (! $ownsTeam && ! $hasPermission) {
                    Log::warning("Gate check failed: User {$user->id} does not have permission {$permission} on team {$team->id}. Owns team: ".($ownsTeam ? 'YES' : 'NO').', Has direct permission: '.($hasPermission ? 'YES' : 'NO'));

                    return false;
                }

                return true;
            });
        }

        Gate::define('viewLogViewer', function ($user = null) {
            // In production, you should restrict this to specific emails
            return true;
        });
    }
}
