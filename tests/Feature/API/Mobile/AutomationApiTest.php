<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Automation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutomationApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user->refresh();

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_automations()
    {
        Automation::create([
            'team_id' => $this->team->id,
            'name' => 'Auto 1',
            'trigger_type' => 'manual',
            'is_active' => true,
        ]);

        Automation::create([
            'team_id' => $this->team->id,
            'name' => 'Auto 2',
            'trigger_type' => 'manual',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/mobile/automations', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_can_show_automation()
    {
        $automation = Automation::create([
            'team_id' => $this->team->id,
            'name' => 'Specific Auto',
            'trigger_type' => 'manual',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/mobile/automations/{$automation->id}", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Specific Auto')
            ->assertJsonStructure([
                'id',
                'name',
                'trigger_type',
                'is_active',
                'runs_count',
                'steps_count',
            ]);
    }

    public function test_cannot_show_automation_of_another_team()
    {
        $otherTeam = Team::factory()->create();
        $automation = Automation::create([
            'team_id' => $otherTeam->id,
            'name' => 'Other Team Auto',
            'trigger_type' => 'manual',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/mobile/automations/{$automation->id}", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(403);
    }
}
