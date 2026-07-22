<?php

namespace Tests\Feature\Health;

use App\Models\Campaign;
use App\Models\Team;
use App\Models\WhatsAppHealthAlert;
use App\Services\BroadcastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * campaign:process runs every minute. Before this, a campaign that could not
 * launch stayed 'scheduled' and was retried forever, logging an error each time
 * while the customer saw it stuck with no explanation.
 */
class CampaignRetryGraceTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
    }

    private function campaign(\DateTimeInterface $scheduledAt): Campaign
    {
        return Campaign::create([
            'team_id' => $this->team->id,
            'name' => 'Stuck Campaign',
            'status' => 'scheduled',
            'template_name' => 'hello_world',
            'total_contacts' => 10,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    private function failLaunch(): void
    {
        $this->mock(BroadcastService::class)
            ->shouldReceive('launch')
            ->andThrow(new \Exception('WhatsApp Health Issues Detected (Access token expired)'));
    }

    public function test_a_recent_failure_stays_scheduled_for_retry(): void
    {
        $this->failLaunch();
        $campaign = $this->campaign(now()->subMinutes(5));

        $this->artisan('campaign:process')->assertSuccessful();

        // Inside the grace window a transient issue may still clear itself.
        $this->assertSame('scheduled', $campaign->fresh()->status);
    }

    public function test_it_gives_up_once_the_grace_window_has_passed(): void
    {
        $this->failLaunch();
        $campaign = $this->campaign(now()->subMinutes(90));

        $this->artisan('campaign:process')->assertSuccessful();

        $campaign->refresh();

        $this->assertSame('failed', $campaign->status);
        $this->assertStringContainsString('Access token expired', $campaign->error_message);
    }

    public function test_giving_up_raises_an_alert_the_customer_can_see(): void
    {
        $this->failLaunch();
        $this->campaign(now()->subMinutes(90));

        $this->artisan('campaign:process')->assertSuccessful();

        $alert = WhatsAppHealthAlert::where('team_id', $this->team->id)->first();

        $this->assertNotNull($alert, 'A permanently failed campaign must surface an alert, not just a log line');
        $this->assertSame('campaign_launch_failed', $alert->alert_type);
        $this->assertStringContainsString('Stuck Campaign', $alert->message);
    }

    public function test_a_failed_campaign_is_not_picked_up_again(): void
    {
        $this->failLaunch();
        $campaign = $this->campaign(now()->subMinutes(90));

        $this->artisan('campaign:process');
        $this->assertSame('failed', $campaign->fresh()->status);

        // Second run must find nothing due — the loop is genuinely closed.
        $this->artisan('campaign:process')->expectsOutput('No due campaigns found.');
        $this->assertSame(1, WhatsAppHealthAlert::where('team_id', $this->team->id)->count());
    }
}
