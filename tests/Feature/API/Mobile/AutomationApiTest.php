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

        // 404, not 403: TenantScope hides the other team's automation entirely,
        // so route-model binding fails before the policy is ever consulted.
        // That is the stronger answer — 403 would confirm the record exists.
        $response->assertStatus(404);
    }

    public function test_can_create_custom_automation()
    {
        $response = $this->postJson('/api/v1/mobile/automations', [
            'name' => 'Custom Flow',
            'trigger_type' => 'event_webhook',
            'is_active' => true,
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);

        $automation = Automation::where('name', 'Custom Flow')->first();
        $this->assertNotNull($automation);
        $this->assertEquals('event_webhook', $automation->trigger_type);
        $this->assertCount(3, $automation->flow_data['nodes']);
        $this->assertCount(2, $automation->flow_data['edges']);

        // Check step records in DB
        $this->assertDatabaseHas('automation_steps', [
            'automation_id' => $automation->id,
            'type' => 'trigger',
            'order_index' => 0,
        ]);
        $this->assertDatabaseHas('automation_steps', [
            'automation_id' => $automation->id,
            'type' => 'message',
            'order_index' => 1,
        ]);
        $this->assertDatabaseHas('automation_steps', [
            'automation_id' => $automation->id,
            'type' => 'stop',
            'order_index' => 2,
        ]);

        // Check that validation passes
        $validation = $automation->validate();
        $this->assertTrue($validation['is_activatable']);
        $this->assertEquals(0, $validation['critical_errors']);
    }

    public function test_can_create_birthday_wishes_template_automation()
    {
        $response = $this->postJson('/api/v1/mobile/automations', [
            'name' => 'Birthday wishes',
            'trigger_type' => 'birthday_event',
            'is_active' => true,
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);

        $automation = Automation::where('name', 'Birthday wishes')->first();
        $this->assertNotNull($automation);
        $this->assertEquals('birthday_event', $automation->trigger_type);
        $this->assertStringContainsString('Happy Birthday', $automation->flow_data['nodes'][1]['data']['text']);

        // Check that validation passes
        $validation = $automation->validate();
        $this->assertTrue($validation['is_activatable']);
        $this->assertEquals(0, $validation['critical_errors']);
    }
}
