<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\InternalNote;
use App\Models\Team;
use App\Models\User;
use App\Livewire\Chat\ContactDetails;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HumanInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_assign_conversation_to_self()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->call('assignToSelf');

        $this->assertEquals($user->id, $conversation->fresh()->assigned_to);
    }

    public function test_can_add_internal_note()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->set('newNoteBody', 'This is a private note.')
            ->call('addNote');

        $this->assertDatabaseHas('internal_notes', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'This is a private note.'
        ]);
    }

    public function test_can_view_internal_notes()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id]);

        InternalNote::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Existing note',
            'created_at' => now()->subHour()
        ]);

        Livewire::actingAs($user)
            ->test(ContactDetails::class, ['conversationId' => $conversation->id])
            ->assertSee('Existing note');
    }

    public function test_can_close_conversation_with_reason()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id, 'status' => 'open']);

        // Test MessageWindow component for closing
        Livewire::actingAs($user)
            ->test(\App\Livewire\Chat\MessageWindow::class, ['conversationId' => $conversation->id])
            ->call('closeConversation', 'spam');

        $this->assertEquals('closed', $conversation->fresh()->status);
        $this->assertEquals('spam', $conversation->fresh()->close_reason);
        $this->assertNotNull($conversation->fresh()->closed_at);
    }
}
