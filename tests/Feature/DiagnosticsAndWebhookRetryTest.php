<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Jobs\ExecuteOutboundWebhookJob;
use App\Livewire\Teams\WhatsappConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DiagnosticsAndWebhookRetryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create([
            'user_id' => $this->user->id,
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_access_token' => 'test-token',
            'whatsapp_business_account_id' => '987654321',
        ]);
        $this->user->switchTeam($this->team);
    }

    /**
     * Test ExecuteOutboundWebhookJob handles 404 client error gracefully without retrying.
     */
    public function test_webhook_job_does_not_retry_on_404_client_error()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Not Found', 404),
        ]);

        $sub = WebhookSubscription::create([
            'team_id' => $this->team->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['message.sent'],
            'is_active' => true,
        ]);

        $job = new ExecuteOutboundWebhookJob($sub->id, 'message.sent', ['id' => 1]);
        
        // This should run without throwing any Exception
        $job->handle();

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_subscription_id' => $sub->id,
            'status_code' => 404,
        ]);
    }

    /**
     * Test ExecuteOutboundWebhookJob retries (throws Exception) on 500 server error.
     */
    public function test_webhook_job_throws_exception_to_retry_on_500_server_error()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Server Error', 500),
        ]);

        $sub = WebhookSubscription::create([
            'team_id' => $this->team->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['message.sent'],
            'is_active' => true,
        ]);

        $job = new ExecuteOutboundWebhookJob($sub->id, 'message.sent', ['id' => 1]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Webhook failed with status: 500');

        $job->handle();
    }

    /**
     * Test Run Diagnostics contains quality and message delivery stats.
     */
    public function test_run_diagnostics_contains_quality_and_delivery_stats()
    {
        $this->actingAs($this->user);

        // Mock external verification engines if necessary
        $mockEngine = \Mockery::mock(\App\Services\WhatsAppVerificationEngine::class);
        $mockEngine->shouldReceive('setTeam')->andReturnSelf();
        $mockEngine->shouldReceive('verify')->andReturn([
            'state' => \App\Enums\IntegrationState::AUTHENTICATED,
        ]);
        $this->app->instance(\App\Services\WhatsAppVerificationEngine::class, $mockEngine);

        $component = Livewire::test(WhatsappConfig::class)
            ->call('runSetupDiagnostics')
            ->assertSet('showSetupDiagnostics', true)
            ->assertHasNoErrors();

        // Get value and assert structure
        $diagnostics = $component->instance()->setupDiagnostics;
        
        $this->assertArrayHasKey('quality_and_delivery', $diagnostics);
        $qd = $diagnostics['quality_and_delivery'];
        
        $this->assertArrayHasKey('quality_rating', $qd);
        $this->assertArrayHasKey('messaging_limit_tier', $qd);
        $this->assertArrayHasKey('daily_limit', $qd);
        $this->assertArrayHasKey('current_24h_usage', $qd);
        $this->assertArrayHasKey('remaining_quota', $qd);
        $this->assertArrayHasKey('is_throttled_or_limit_exceeded', $qd);
        $this->assertArrayHasKey('blocking_issues', $qd);
        $this->assertArrayHasKey('messages_sent_last_24h', $qd);
        $this->assertArrayHasKey('messages_failed_last_24h', $qd);
        $this->assertArrayHasKey('delivery_failure_rate_24h', $qd);
    }

    /** Page mounts and lazy-loads for a connected team without error. */
    public function test_page_mounts_and_loads_for_connected_team()
    {
        Http::fake(['*' => Http::response([], 200)]);
        $this->actingAs($this->user);

        Livewire::test(WhatsappConfig::class)
            ->assertSet('is_whatsmark_connected', true)
            ->call('loadData')
            ->assertSet('readyToLoad', true)
            ->assertHasNoErrors();
    }

    /** Governance alert must NOT falsely fire on load: a token with no expiry is long-lived. */
    public function test_governance_alert_not_falsely_triggered_for_permanent_token()
    {
        $this->actingAs($this->user); // team in setUp has no whatsapp_token_expires_at

        $c = Livewire::test(WhatsappConfig::class)->instance();

        $this->assertTrue($c->token_valid, 'permanent token should be valid');
        $this->assertGreaterThanOrEqual(7, $c->tokenDaysUntilExpiry, 'must not trip the <7-day alert');
    }

    /** An actually-expired token is flagged invalid so the alert is truthful. */
    public function test_expired_token_is_flagged_invalid()
    {
        $this->team->update(['whatsapp_token_expires_at' => now()->subDay()]);
        $this->actingAs($this->user);

        $c = Livewire::test(WhatsappConfig::class)->instance();

        $this->assertFalse($c->token_valid);
        $this->assertLessThan(7, $c->tokenDaysUntilExpiry);
    }

    /** Outbound webhook field actually wires into the delivery pipeline (and unwires on clear). */
    public function test_outbound_webhook_creates_and_removes_subscription()
    {
        $this->actingAs($this->user);

        Livewire::test(WhatsappConfig::class)
            ->set('outbound_webhook_url', 'https://example.com/hook')
            ->call('updateOutboundWebhook')
            ->assertHasNoErrors();

        $sub = \App\Models\WebhookSubscription::where('team_id', $this->team->id)
            ->where('name', 'Setup Page Forwarder')->first();
        $this->assertNotNull($sub, 'subscription should be created');
        $this->assertSame('https://example.com/hook', $sub->url);
        $this->assertTrue($sub->isSubscribedTo('message.sent'), 'empty events = all events');

        // Clearing the URL removes the forwarder.
        Livewire::test(WhatsappConfig::class)
            ->set('outbound_webhook_url', '')
            ->call('updateOutboundWebhook');

        $this->assertDatabaseMissing('webhook_subscriptions', [
            'team_id' => $this->team->id,
            'name' => 'Setup Page Forwarder',
        ]);
    }

    /** Edit Profile loads current Meta values so the form isn't blank. */
    public function test_edit_profile_loads_current_values()
    {
        Http::fake([
            '*/whatsapp_business_profile*' => Http::response([
                'data' => [['about' => 'We sell widgets', 'description' => 'Widget Co', 'email' => 'hi@widget.co']],
            ], 200),
            '*' => Http::response([], 200),
        ]);
        $this->actingAs($this->user);

        Livewire::test(WhatsappConfig::class)
            ->call('editProfile')
            ->assertSet('is_editing_profile', true)
            ->assertSet('profile_description', 'Widget Co')
            ->assertSet('profile_email', 'hi@widget.co');
    }

    /** Behavior toggles + timezone actually persist to the team (calling disabled = no Meta call). */
    public function test_behavior_settings_persist_to_team()
    {
        $this->actingAs($this->user);

        Livewire::test(WhatsappConfig::class)
            ->set('timezone', 'Asia/Kolkata')
            ->set('callingEnabled', false)
            ->set('callButtonVisible', true)
            ->set('callbackPermissionEnabled', true)
            ->call('updateBehaviorSettings')
            ->assertHasNoErrors();

        $team = $this->team->fresh();
        $this->assertSame('Asia/Kolkata', $team->timezone);
        $this->assertSame('disabled', $team->whatsapp_settings['calling']['status']);
        $this->assertSame('show', $team->whatsapp_settings['calling']['call_icon_visibility']);
        $this->assertSame('enabled', $team->whatsapp_settings['calling']['callback_permission_status']);
    }

    /** Outbound webhook input persists to the Team column and round-trips on reload. */
    public function test_outbound_webhook_persists()
    {
        $this->actingAs($this->user);

        Livewire::test(WhatsappConfig::class)
            ->set('outbound_webhook_url', 'https://example.com/hook')
            ->call('updateOutboundWebhook')
            ->assertHasNoErrors()
            ->assertSet('is_webhook_connected', true);

        $this->assertSame('https://example.com/hook', $this->team->fresh()->outbound_webhook_url);
    }

    /** Regression: notify events carry message/type (positional dispatch showed no toast). */
    public function test_disconnect_dispatches_named_notify_and_clears_connection()
    {
        $this->actingAs($this->user);

        Livewire::test(WhatsappConfig::class)
            ->set('disconnectConfirmation', 'DISCONNECT')
            ->call('disconnect')
            ->assertDispatched('notify', fn ($event, $params) => array_key_exists('message', $params) && array_key_exists('type', $params));

        $team = $this->team->fresh();
        $this->assertFalse((bool) $team->whatsapp_connected);
        $this->assertNull($team->whatsapp_access_token);
    }
}
