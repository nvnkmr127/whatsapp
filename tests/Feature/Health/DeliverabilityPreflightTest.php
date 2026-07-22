<?php

namespace Tests\Feature\Health;

use App\Models\Team;
use App\Models\WhatsAppHealthSnapshot;
use App\Services\Health\DeliverabilityPreflight;
use App\Services\WhatsAppHealthMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverabilityPreflightTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->team = Team::factory()->create();
    }

    private function preflight(array $blockingIssues = []): DeliverabilityPreflight
    {
        $monitor = $this->mock(WhatsAppHealthMonitor::class);
        $monitor->shouldReceive('getBlockingIssues')->andReturn($blockingIssues);

        return new DeliverabilityPreflight($monitor);
    }

    private function snapshot(array $attrs = []): void
    {
        WhatsAppHealthSnapshot::create(array_merge([
            'team_id' => $this->team->id,
            'health_score' => 80,
            'health_status' => 'healthy',
            'quality_rating' => 'GREEN',
            'daily_limit' => 1000,
            'current_usage' => 0,
            'snapshot_at' => now(),
        ], $attrs));
    }

    public function test_blocking_issues_pass_straight_through_from_the_monitor(): void
    {
        $this->snapshot();

        // Same source BroadcastService gates on, so the warning cannot drift
        // from the thing that actually refuses the send.
        $result = $this->preflight(['Access token expired'])->check($this->team, 100);

        $this->assertSame(['Access token expired'], $result['blocking']);
    }

    public function test_it_warns_when_the_audience_exceeds_remaining_daily_capacity(): void
    {
        $this->snapshot(['daily_limit' => 1000, 'current_usage' => 900]);

        $result = $this->preflight()->check($this->team, 500);

        $this->assertSame(100, $result['remaining']);
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('400 message(s) will not send today', $result['warnings'][0]);
    }

    public function test_no_capacity_warning_when_the_audience_fits(): void
    {
        $this->snapshot(['daily_limit' => 1000, 'current_usage' => 100]);

        $this->assertSame([], $this->preflight()->check($this->team, 500)['warnings']);
    }

    public function test_a_red_rating_warns_about_restriction_risk(): void
    {
        $this->snapshot(['quality_rating' => 'RED']);

        $result = $this->preflight()->check($this->team, 10);

        $this->assertSame('RED', $result['quality_rating']);
        $this->assertStringContainsString('RED', $result['warnings'][0]);
    }

    public function test_a_yellow_rating_warns_more_gently(): void
    {
        $this->snapshot(['quality_rating' => 'YELLOW']);

        $result = $this->preflight()->check($this->team, 10);

        $this->assertStringContainsString('YELLOW', $result['warnings'][0]);
    }

    public function test_usage_is_never_reported_as_negative_remaining(): void
    {
        // Meta can report usage above the limit at tier boundaries.
        $this->snapshot(['daily_limit' => 1000, 'current_usage' => 1500]);

        $this->assertSame(0, $this->preflight()->check($this->team, 10)['remaining']);
    }

    public function test_it_degrades_quietly_with_no_snapshot(): void
    {
        $result = $this->preflight()->check($this->team, 5000);

        // Nothing recorded yet is not the same as "you are over your limit".
        $this->assertNull($result['daily_limit']);
        $this->assertNull($result['remaining']);
        $this->assertNull($result['quality_rating']);
        $this->assertSame([], $result['warnings']);
    }
}
