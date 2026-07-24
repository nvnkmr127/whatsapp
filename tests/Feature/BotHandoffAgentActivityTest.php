<?php

namespace Tests\Feature;

use App\Livewire\Chat\MessageWindow;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BotHandoffAgentActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_sending_message_pauses_bot_for_contact(): void
    {
        Storage::fake('public');

        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'is_bot_paused' => false,
        ]);

        $conversation = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
        ]);

        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::test(MessageWindow::class, ['conversationId' => $conversation->id])
            ->set('newAttachment', $file)
            ->call('sendMessage');

        $this->assertTrue($contact->fresh()->is_bot_paused);
    }
}
