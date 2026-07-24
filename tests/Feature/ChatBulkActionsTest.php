<?php

namespace Tests\Feature;

use App\Livewire\Chat\ConversationList;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_mark_as_read_updates_selected_conversations_inbound_messages(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $conv1 = Conversation::factory()->create(['team_id' => $team->id]);
        $conv2 = Conversation::factory()->create(['team_id' => $team->id]);

        $msg1 = Message::factory()->create([
            'team_id' => $team->id,
            'conversation_id' => $conv1->id,
            'direction' => 'inbound',
            'read_at' => null,
        ]);

        $msg2 = Message::factory()->create([
            'team_id' => $team->id,
            'conversation_id' => $conv2->id,
            'direction' => 'inbound',
            'read_at' => null,
        ]);

        Livewire::test(ConversationList::class)
            ->set('selectedConversationIds', [$conv1->id, $conv2->id])
            ->call('bulkMarkAsRead')
            ->assertSet('selectedConversationIds', []);

        $this->assertNotNull($msg1->fresh()->read_at);
        $this->assertNotNull($msg2->fresh()->read_at);
    }

    public function test_bulk_close_updates_selected_conversations_status_to_closed(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $conv1 = Conversation::factory()->create(['team_id' => $team->id, 'status' => 'open']);
        $conv2 = Conversation::factory()->create(['team_id' => $team->id, 'status' => 'open']);

        Livewire::test(ConversationList::class)
            ->set('selectedConversationIds', [$conv1->id, $conv2->id])
            ->call('bulkClose')
            ->assertSet('selectedConversationIds', []);

        $this->assertSame('closed', $conv1->fresh()->status);
        $this->assertSame('closed', $conv2->fresh()->status);
    }
}
