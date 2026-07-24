<?php

namespace Tests\Feature;

use App\Livewire\Chat\ConversationList;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConversationAssignmentTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_conversations_by_assignment_tabs(): void
    {
        $team = Team::factory()->create();
        $user1 = User::factory()->create(['current_team_id' => $team->id]);
        $user2 = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user1);

        $contact1 = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Assigned User 1']);
        $conv1 = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact1->id,
            'assigned_to' => $user1->id,
            'last_message_at' => now(),
        ]);

        $contact2 = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Assigned User 2']);
        $conv2 = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact2->id,
            'assigned_to' => $user2->id,
            'last_message_at' => now(),
        ]);

        $contact3 = Contact::factory()->create(['team_id' => $team->id, 'name' => 'Unassigned Contact']);
        $conv3 = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact3->id,
            'assigned_to' => null,
            'last_message_at' => now(),
        ]);

        // Mine tab
        Livewire::test(ConversationList::class)
            ->set('filterAssignment', 'mine')
            ->assertSee($contact1->name)
            ->assertDontSee($contact2->name)
            ->assertDontSee($contact3->name);

        // Unassigned tab
        Livewire::test(ConversationList::class)
            ->set('filterAssignment', 'unassigned')
            ->assertSee($contact3->name)
            ->assertDontSee($contact1->name)
            ->assertDontSee($contact2->name);
    }
}
