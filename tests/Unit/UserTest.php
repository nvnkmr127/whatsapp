<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_relations_prevent_lazy_loading_exceptions(): void
    {
        Model::preventLazyLoading(true);

        $owner = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $owner->id]);
        $owner->forceFill(['current_team_id' => $team->id])->save();

        $freshUser = User::find($owner->id);

        // Accessing teams & currentTeam dynamic relationship properties
        $currentTeam = $freshUser->currentTeam;
        $teams = $freshUser->teams;

        $this->assertNotNull($currentTeam);
        $this->assertEquals($team->id, $currentTeam->id);

        Model::preventLazyLoading(false);
    }
}
