<?php

namespace Tests\Feature;

use App\Livewire\Chat\ContactDetails;
use App\Livewire\Chat\MessageWindow;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    private function team(): array
    {
        $team = Team::factory()->create(['is_sandbox_mode' => true]);
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '1555'.rand(1000000, 9999999)]);
        $conversation = Conversation::factory()->create(['team_id' => $team->id, 'contact_id' => $contact->id]);

        return compact('team', 'user', 'contact', 'conversation');
    }

    public function test_forward_message_cannot_target_another_teams_conversation(): void
    {
        $a = $this->team();
        $b = $this->team();

        $msgA = Message::create([
            'team_id' => $a['team']->id,
            'contact_id' => $a['contact']->id,
            'conversation_id' => $a['conversation']->id,
            'direction' => 'inbound',
            'status' => 'sent',
            'type' => 'text',
            'content' => 'secret',
        ]);

        $this->actingAs($a['user']);

        $component = Livewire::test(MessageWindow::class, ['conversationId' => $a['conversation']->id])
            ->set('forwardingMessageId', $msgA->id)
            ->set('forwardSelectedConversations', [$b['conversation']->id])
            ->call('forwardMessage');

        // Nothing may be injected into team B's conversation.
        $this->assertSame(0, Message::where('conversation_id', $b['conversation']->id)->count());
    }

    public function test_retry_media_download_rejects_foreign_message(): void
    {
        $a = $this->team();
        $b = $this->team();

        $foreign = Message::create([
            'team_id' => $b['team']->id,
            'contact_id' => $b['contact']->id,
            'conversation_id' => $b['conversation']->id,
            'direction' => 'inbound',
            'status' => 'sent',
            'type' => 'image',
            'media_id' => 'wamid.FOREIGN',
        ]);

        $this->actingAs($a['user']);

        $res = Livewire::test(MessageWindow::class, ['conversationId' => $a['conversation']->id])
            ->instance()
            ->retryMediaDownload($foreign->id);

        $this->assertSame('error', $res['status']);
    }

    public function test_contact_details_does_not_load_foreign_conversation(): void
    {
        $a = $this->team();
        $b = $this->team();

        $this->actingAs($a['user']);

        $instance = Livewire::test(ContactDetails::class, ['conversationId' => $b['conversation']->id])
            ->instance();

        $this->assertNull($instance->conversation);
    }
}
