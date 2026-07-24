<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationTag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Chat\ContactDetails;
use App\Livewire\Chat\ConversationList;

class ConversationTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_attach_and_detach_tags_from_conversation()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);
        $tag = ConversationTag::create(['team_id' => $team->id, 'name' => 'VIP', 'color_code' => 'text-wa-teal']);

        $this->actingAs($user);

        Livewire::test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->call('toggleConversationTag', $tag->id)
            ->assertDispatched('contact-updated');

        $this->assertTrue($conversation->fresh()->tags->contains($tag));

        Livewire::test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->call('toggleConversationTag', $tag->id);

        $this->assertFalse($conversation->fresh()->tags->contains($tag));
    }

    public function test_conversation_list_filters_by_tag()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        
        $conv1 = Conversation::factory()->create(['team_id' => $team->id]);
        $conv2 = Conversation::factory()->create(['team_id' => $team->id]);
        
        $tag = ConversationTag::create(['team_id' => $team->id, 'name' => 'Urgent']);
        $conv1->tags()->attach($tag->id);

        $this->actingAs($user);

        Livewire::test(ConversationList::class)
            ->set('filterTagId', $tag->id)
            ->assertSee($conv1->contact->name)
            ->assertDontSee($conv2->contact->name);
    }
}
