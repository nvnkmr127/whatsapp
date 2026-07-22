<?php

namespace Tests\Feature\Health;

use App\Livewire\Health\DeliverabilityCenter;
use App\Models\QualityRatingHistory;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppHealthAlert;
use App\Models\WhatsAppHealthSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliverabilityCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->actingAs($this->user);
    }

    private function snapshot(array $attrs = []): WhatsAppHealthSnapshot
    {
        return WhatsAppHealthSnapshot::create(array_merge([
            'team_id' => $this->team->id,
            'health_score' => 80,
            'health_status' => 'healthy',
            'quality_rating' => 'GREEN',
            'messaging_tier' => 'TIER_1K',
            'daily_limit' => 1000,
            'usage_percent' => 10,
            'snapshot_at' => now(),
        ], $attrs));
    }

    public function test_it_renders_without_history(): void
    {
        Livewire::test(DeliverabilityCenter::class)
            ->assertOk()
            ->assertSee('No health history yet');
    }

    public function test_it_shows_current_state_from_the_latest_snapshot(): void
    {
        $this->snapshot(['snapshot_at' => now()->subDay(), 'health_score' => 40, 'quality_rating' => 'RED']);
        $this->snapshot(['snapshot_at' => now(), 'health_score' => 90, 'quality_rating' => 'GREEN']);

        Livewire::test(DeliverabilityCenter::class)
            ->assertSee('GREEN')
            ->assertSee('90');
    }

    public function test_it_rolls_snapshots_up_per_day(): void
    {
        // Two readings the same day averaging 60, one the next day at 90.
        $this->snapshot(['snapshot_at' => now()->subDays(2)->setTime(9, 0), 'health_score' => 40]);
        $this->snapshot(['snapshot_at' => now()->subDays(2)->setTime(21, 0), 'health_score' => 80]);
        $this->snapshot(['snapshot_at' => now()->subDay()->setTime(9, 0), 'health_score' => 90]);

        $trend = Livewire::test(DeliverabilityCenter::class)->viewData('trend');

        $this->assertCount(2, $trend, 'Three snapshots across two days should roll up to two points');
        $this->assertSame(60, $trend[0]['score']);
        $this->assertSame(90, $trend[1]['score']);
    }

    public function test_delta_reports_direction_of_travel(): void
    {
        $this->snapshot(['snapshot_at' => now()->subDays(2), 'health_score' => 90]);
        $this->snapshot(['snapshot_at' => now()->subDay(), 'health_score' => 60]);

        // The whole point of the page: a falling number, not just a current one.
        $this->assertSame(-30, Livewire::test(DeliverabilityCenter::class)->viewData('delta'));
    }

    public function test_range_filter_excludes_older_readings(): void
    {
        $this->snapshot(['snapshot_at' => now()->subDays(45), 'health_score' => 20]);
        $this->snapshot(['snapshot_at' => now()->subDays(2), 'health_score' => 80]);

        $component = Livewire::test(DeliverabilityCenter::class);

        $this->assertCount(1, $component->call('setRange', 7)->viewData('trend'));
        $this->assertCount(2, $component->call('setRange', 90)->viewData('trend'));
    }

    public function test_acknowledging_an_alert_removes_it_from_the_queue(): void
    {
        $alert = WhatsAppHealthAlert::create([
            'team_id' => $this->team->id,
            'severity' => 'critical',
            'dimension' => 'quality',
            'alert_type' => 'quality_red',
            'message' => 'Quality rating dropped to RED',
        ]);

        Livewire::test(DeliverabilityCenter::class)
            ->assertSee('Quality rating dropped to RED')
            ->call('acknowledge', $alert->id)
            ->assertDontSee('Quality rating dropped to RED');

        $alert->refresh();
        $this->assertTrue($alert->acknowledged);
        $this->assertSame($this->user->id, $alert->acknowledged_by);
    }

    public function test_another_teams_history_is_never_visible(): void
    {
        $otherTeam = Team::factory()->create();

        WhatsAppHealthSnapshot::create([
            'team_id' => $otherTeam->id,
            'health_score' => 5,
            'health_status' => 'critical',
            'quality_rating' => 'RED',
            'snapshot_at' => now(),
        ]);

        QualityRatingHistory::create([
            'team_id' => $otherTeam->id,
            'previous_rating' => 'GREEN',
            'new_rating' => 'RED',
            'severity' => 'critical',
        ]);

        $component = Livewire::test(DeliverabilityCenter::class)
            ->assertSee('No health history yet');

        $this->assertCount(0, $component->viewData('trend'));
        $this->assertCount(0, $component->viewData('ratingChanges'));
    }
}
