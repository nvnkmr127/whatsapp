<?php

namespace Tests\Feature;

use App\Livewire\Teams\MembersManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpdateTeamMemberRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_member_roles_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        Livewire::test(MembersManager::class, ['team' => $user->currentTeam])
            ->set('managingRoleFor', $otherUser)
            ->set('currentRole', 'manager')
            ->call('updateRole');

        $this->assertTrue($otherUser->fresh()->hasTeamRole(
            $user->currentTeam->fresh(), 'manager'
        ));
    }

    public function test_admins_can_update_team_member_roles(): void
    {
        // Policy deliberately grants owner OR admin the right to manage members.
        $owner = User::factory()->withPersonalTeam()->create();
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $owner->currentTeam->users()->attach($admin, ['role' => 'admin']);
        $owner->currentTeam->users()->attach($target, ['role' => 'agent']);

        $this->actingAs($admin);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->set('managingRoleFor', $target)
            ->set('currentRole', 'manager')
            ->call('updateRole');

        $this->assertDatabaseHas('team_user', [
            'team_id' => $owner->currentTeam->id,
            'user_id' => $target->id,
            'role' => 'manager',
        ]);
    }

    public function test_non_privileged_members_cannot_update_team_member_roles(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();

        $owner->currentTeam->users()->attach(
            $agent = User::factory()->create(), ['role' => 'agent']
        );

        $this->actingAs($agent);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->set('managingRoleFor', $agent)
            ->set('currentRole', 'manager')
            ->call('updateRole')
            ->assertStatus(403);

        $this->assertTrue($agent->fresh()->hasTeamRole(
            $owner->currentTeam->fresh(), 'agent'
        ));
    }
}
