<?php

namespace Tests\Feature;

use App\Livewire\Chat\AiAssistant;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiAssistantSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_assistant_blocks_fetching_other_team_conversation_messages(): void
    {
        $teamA = Team::factory()->create(['name' => 'Team Alpha']);
        $teamB = Team::factory()->create(['name' => 'Team Beta']);

        $userA = User::factory()->create(['current_team_id' => $teamA->id]);
        $conversationB = Conversation::factory()->create(['team_id' => $teamB->id]);

        Message::factory()->create([
            'conversation_id' => $conversationB->id,
            'content' => 'Secret message belonging to Team Beta',
            'direction' => 'inbound',
        ]);

        $this->actingAs($userA);

        $component = Livewire::test(AiAssistant::class)
            ->call('generateSuggestions', $conversationB->id);

        $suggestions = $component->get('suggestions');

        $this->assertSame(['Conversation not found.'], $suggestions);
    }
}
