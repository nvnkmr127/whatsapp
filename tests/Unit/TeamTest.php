<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_users_prevents_lazy_loading_exception(): void
    {
        Model::preventLazyLoading(true);

        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'agent']);

        // Fetch fresh instance without preloaded relations
        $freshTeam = Team::find($team->id);

        $allUsers = $freshTeam->allUsers();

        $this->assertCount(2, $allUsers);
        $this->assertTrue($allUsers->contains('id', $owner->id));
        $this->assertTrue($allUsers->contains('id', $member->id));

        Model::preventLazyLoading(false);
    }

    public function test_users_relation_access_prevents_lazy_loading_exception(): void
    {
        Model::preventLazyLoading(true);

        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $member = User::factory()->create();
        $team->users()->attach($member, ['role' => 'agent']);

        $freshTeam = Team::find($team->id);

        $users = $freshTeam->users;

        $this->assertCount(1, $users);
        $this->assertEquals($member->id, $users->first()->id);

        Model::preventLazyLoading(false);
    }
}
