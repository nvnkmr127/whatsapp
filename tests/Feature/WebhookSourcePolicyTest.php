<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\WebhookSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSourcePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_any_webhook_source()
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $team = Team::factory()->create();
        $source = WebhookSource::create([
            'team_id' => $team->id,
            'name' => 'Test Source',
            'platform' => 'custom',
            'auth_method' => 'none',
        ]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('update', $source));
    }

    public function test_user_can_update_their_own_team_webhook_source()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->switchTeam($team);

        $source = WebhookSource::create([
            'team_id' => $team->id,
            'name' => 'My Source',
            'platform' => 'custom',
            'auth_method' => 'none',
        ]);

        $this->actingAs($user);

        $this->assertTrue($user->can('update', $source));
    }

    public function test_user_cannot_update_other_team_webhook_source()
    {
        $user = User::factory()->create();
        $myTeam = Team::factory()->create(['user_id' => $user->id]);
        $user->switchTeam($myTeam);

        $otherTeam = Team::factory()->create();
        $source = WebhookSource::create([
            'team_id' => $otherTeam->id,
            'name' => 'Other Source',
            'platform' => 'custom',
            'auth_method' => 'none',
        ]);

        $this->actingAs($user);

        $this->assertFalse($user->can('update', $source));
    }
}
