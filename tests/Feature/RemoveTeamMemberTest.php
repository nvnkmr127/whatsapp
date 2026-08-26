<?php

namespace Tests\Feature;

use App\Livewire\Teams\MembersManager;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RemoveTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_can_be_removed_from_teams(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        Livewire::test(MembersManager::class, ['team' => $user->currentTeam])
            ->set('teamMemberIdBeingRemoved', $otherUser->id)
            ->call('removeTeamMember');

        $this->assertCount(0, $user->currentTeam->fresh()->users);
    }

    public function test_removing_a_member_unassigns_their_active_conversations(): void
    {
        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $user->currentTeam->users()->attach(
            $otherUser = User::factory()->create(), ['role' => 'admin']
        );

        $active = Contact::factory()->create([
            'team_id' => $user->currentTeam->id,
            'assigned_to' => $otherUser->id,
        ]);

        Livewire::test(MembersManager::class, ['team' => $user->currentTeam])
            ->set('teamMemberIdBeingRemoved', $otherUser->id)
            ->call('removeTeamMember');

        $this->assertNull($active->fresh()->assigned_to);
    }

    public function test_admins_can_remove_team_members(): void
    {
        // Policy deliberately grants owner OR admin the right to manage members.
        $owner = User::factory()->withPersonalTeam()->create();
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $owner->currentTeam->users()->attach($admin, ['role' => 'admin']);
        $owner->currentTeam->users()->attach($target, ['role' => 'agent']);

        $this->actingAs($admin);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->set('teamMemberIdBeingRemoved', $target->id)
            ->call('removeTeamMember');

        $this->assertDatabaseMissing('team_user', [
            'team_id' => $owner->currentTeam->id,
            'user_id' => $target->id,
        ]);
    }

    public function test_non_privileged_members_cannot_remove_team_members(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();

        $owner->currentTeam->users()->attach(
            $agent = User::factory()->create(), ['role' => 'agent']
        );

        $this->actingAs($agent);

        Livewire::test(MembersManager::class, ['team' => $owner->currentTeam])
            ->set('teamMemberIdBeingRemoved', $owner->id)
            ->call('removeTeamMember')
            ->assertStatus(403);
    }
}
