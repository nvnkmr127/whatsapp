<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateApiTest extends TestCase
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

        Sanctum::actingAs($this->user);
    }

    public function test_admin_can_create_template_draft()
    {
        $payload = [
            'name' => 'welcome_offer',
            'category' => 'MARKETING',
            'language' => 'en_US',
            'components' => [
                ['type' => 'BODY', 'text' => 'Welcome to our store!'],
            ],
        ];

        $response = $this->postJson('/api/v1/mobile/templates', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'DRAFT');

        $this->assertDatabaseHas('whatsapp_templates', ['name' => 'welcome_offer', 'status' => 'DRAFT']);
    }

    public function test_admin_can_toggle_template_status()
    {
        $template = WhatsappTemplate::factory()->create(['team_id' => $this->team->id, 'is_paused' => false]);

        $response = $this->postJson("/api/v1/mobile/templates/{$template->id}/toggle", [], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_paused', true);
    }

    public function test_data_isolation_between_teams()
    {
        $otherTeam = Team::factory()->create();
        $otherTemplate = WhatsappTemplate::factory()->create(['team_id' => $otherTeam->id]);

        $response = $this->getJson("/api/v1/mobile/templates/{$otherTemplate->id}");

        $response->assertStatus(403);
    }
}
