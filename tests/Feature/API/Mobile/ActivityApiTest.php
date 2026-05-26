<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityApiTest extends TestCase
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

    public function test_can_list_activities()
    {
        ActivityLog::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'action' => 'settings.updated',
            'description' => 'Test log description one',
        ]);

        $response = $this->getJson('/api/v1/mobile/activities', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.description', 'Test log description one');
    }

    public function test_can_show_activity()
    {
        $log = ActivityLog::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'action' => 'settings.updated',
            'description' => 'Test log description two',
        ]);

        $response = $this->getJson("/api/v1/mobile/activities/{$log->id}", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('id', $log->id);
        $response->assertJsonPath('description', 'Test log description two');
    }
}
