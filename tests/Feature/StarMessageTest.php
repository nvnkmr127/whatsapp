<?php

namespace Tests\Feature;

use App\Livewire\Chat\MessageWindow;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StarMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_toggle_starred_status_on_message(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
        ]);

        $message = Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'Important order number #12345',
            'is_starred' => false,
        ]);

        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->call('toggleStarMessage', $message->id);

        $this->assertTrue($message->fresh()->is_starred);

        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->call('toggleStarMessage', $message->id);

        $this->assertFalse($message->fresh()->is_starred);
    }
}
