<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\MessageBot;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageBotApiTest extends TestCase
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

    public function test_admin_can_list_bots()
    {
        MessageBot::factory()->count(3)->create(['team_id' => $this->team->id]);

        $response = $this->getJson('/api/v1/mobile/bots', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_bot()
    {
        $payload = [
            'name' => 'New Test Bot',
            'trigger' => ['hello', 'hi'],
            'reply_text' => 'Hello there!',
        ];

        $response = $this->postJson('/api/v1/mobile/bots', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'New Test Bot');

        $this->assertDatabaseHas('message_bots', ['name' => 'New Test Bot']);
    }

    public function test_admin_can_duplicate_bot()
    {
        $bot = MessageBot::factory()->create(['team_id' => $this->team->id, 'name' => 'Original']);

        $response = $this->postJson("/api/v1/mobile/bots/{$bot->id}/duplicate", [], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Original (Copy)');
    }

    public function test_admin_can_delete_bot()
    {
        $bot = MessageBot::factory()->create(['team_id' => $this->team->id]);

        $response = $this->deleteJson("/api/v1/mobile/bots/{$bot->id}", [], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Bot deleted');

        $this->assertDatabaseMissing('message_bots', ['id' => $bot->id]);
    }

    public function test_non_admin_cannot_create_bot()
    {
        $this->user->teams()->updateExistingPivot($this->team->id, ['role' => 'agent']);

        $payload = ['name' => 'Should Fail'];

        $response = $this->postJson('/api/v1/mobile/bots', $payload);

        $response->assertStatus(403);
    }
}
