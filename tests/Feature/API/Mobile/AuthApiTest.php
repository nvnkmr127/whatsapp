<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $otherTeam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Main Team']);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        
        $this->otherTeam = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Second Team']);
        $this->user->teams()->attach($this->otherTeam, ['role' => 'agent']);

        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user->refresh();

        Sanctum::actingAs($this->user);
    }

    public function test_can_get_me_details()
    {
        $response = $this->getJson('/api/v1/mobile/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'phone', 'role'],
                'teams',
                'members',
                'numbers',
                'business_profile',
            ])
            ->assertJsonPath('user.id', $this->user->id)
            ->assertJsonCount(2, 'teams');
    }

    public function test_can_get_teams()
    {
        $response = $this->getJson('/api/v1/mobile/auth/teams');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $this->team->id, 'name' => 'Main Team'])
            ->assertJsonFragment(['id' => $this->otherTeam->id, 'name' => 'Second Team']);
    }

    public function test_can_get_numbers()
    {
        $response = $this->getJson('/api/v1/mobile/auth/numbers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                [
                    'id',
                    'display_number',
                    'verified_name',
                ]
            ]);
    }
}
