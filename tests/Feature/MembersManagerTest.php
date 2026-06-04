<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use App\Livewire\Teams\MembersManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembersManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_team_members(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $response = $this->get('/team/members');

        $response->assertStatus(200);

        Livewire::test(MembersManager::class, ['team' => $user->currentTeam])
            ->assertStatus(200);
    }

    public function test_non_owner_team_member_can_view_team_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $owner->currentTeam->users()->attach(
            $agent = User::factory()->create(), ['role' => 'agent']
        );

        $this->actingAs($agent);
        $agent->switchTeam($owner->currentTeam);

        $response = $this->get('/team/members');
        $response->assertStatus(200);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->assertStatus(200);
    }

    public function test_non_team_member_cannot_view_team_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->assertStatus(403);
    }

    public function test_non_owner_cannot_open_add_member_modal_or_create_user(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $owner->currentTeam->users()->attach(
            $agent = User::factory()->create(), ['role' => 'agent']
        );

        $this->actingAs($agent);
        $agent->switchTeam($owner->currentTeam);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->call('openAddMemberModal')
            ->assertStatus(403);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->call('createUser')
            ->assertStatus(403);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->call('addTeamMember')
            ->assertStatus(403);
    }
}
