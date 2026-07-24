<?php

namespace Tests\Feature;

use App\Livewire\Chat\ChatDashboard;
use App\Livewire\Chat\ConversationList;
use App\Livewire\Chat\MessageWindow;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_deep_link_with_invalid_conversation_id_resets_to_null(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        Livewire::test(ChatDashboard::class, ['activeConversationId' => 999999])
            ->assertSet('activeConversationId', null);
    }

    public function test_deep_link_with_other_team_conversation_id_resets_to_null(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $userA = User::factory()->create(['current_team_id' => $teamA->id]);
        $conversationB = Conversation::factory()->create(['team_id' => $teamB->id]);

        $this->actingAs($userA);

        Livewire::test(ChatDashboard::class, ['activeConversationId' => $conversationB->id])
            ->assertSet('activeConversationId', null);
    }

    public function test_deep_link_with_valid_conversation_id_preserves_active_id(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user);

        Livewire::test(ChatDashboard::class, ['activeConversationId' => $conversation->id])
            ->assertSet('activeConversationId', $conversation->id);
    }

    public function test_older_active_conversation_outside_top_25_is_included_in_conversation_list(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create 30 conversations (newer)
        $newerConversations = Conversation::factory()->count(30)->create([
            'team_id' => $team->id,
            'last_message_at' => now(),
        ]);

        // Create 1 old conversation
        $oldConversation = Conversation::factory()->create([
            'team_id' => $team->id,
            'last_message_at' => now()->subDays(30),
        ]);

        // When activeConversationId is oldConversation->id
        $component = Livewire::test(ConversationList::class, [
            'activeConversationId' => $oldConversation->id,
        ]);

        $conversations = $component->get('conversations');

        $this->assertTrue(
            $conversations->contains('id', $oldConversation->id),
            'The active conversation outside top 25 must be included in the conversation list'
        );
    }
}
