<?php

namespace Tests\Feature;

use App\Livewire\Chat\ConversationList;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StarredConversationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_conversations_by_starred_messages(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $contact1 = Contact::factory()->create(['team_id' => $team->id]);
        $conv1 = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact1->id,
            'last_message_at' => now(),
        ]);
        Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact1->id,
            'conversation_id' => $conv1->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'Normal message',
            'is_starred' => false,
        ]);

        $contact2 = Contact::factory()->create(['team_id' => $team->id]);
        $conv2 = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact2->id,
            'last_message_at' => now(),
        ]);
        Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact2->id,
            'conversation_id' => $conv2->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'Starred message',
            'is_starred' => true,
        ]);

        Livewire::test(ConversationList::class)
            ->set('filterStarred', true)
            ->assertSee($contact2->name)
            ->assertDontSee($contact1->name);
    }

    public function test_bulk_export_streams_text_file(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        $contact = Contact::factory()->create(['team_id' => $team->id]);
        $conv = Conversation::factory()->create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
        ]);
        Message::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conv->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'Test bulk message content',
        ]);

        Livewire::test(ConversationList::class)
            ->set('selectedConversationIds', [$conv->id])
            ->call('bulkExport')
            ->assertFileDownloaded();
    }
}
