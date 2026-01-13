<?php

namespace Tests\Feature\Conversation;

use App\Models\Conversation;
use App\Models\Team;
use App\Models\Contact;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_active_conversation_creates_new_if_none_exist()
    {
        $contact = Contact::factory()->create();
        $service = new ConversationService();

        $conversation = $service->ensureActiveConversation($contact);

        $this->assertNotNull($conversation);
        $this->assertEquals('new', $conversation->status);
        $this->assertEquals($contact->id, $conversation->contact_id);
    }

    public function test_ensure_active_conversation_returns_existing_open_one()
    {
        $contact = Contact::factory()->create();
        $existing = Conversation::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'status' => 'open'
        ]);

        $service = new ConversationService();
        $conversation = $service->ensureActiveConversation($contact);

        $this->assertEquals($existing->id, $conversation->id);
        $this->assertEquals(1, Conversation::count());
    }

    public function test_incoming_message_updates_status_to_open()
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'status' => 'waiting_reply'
        ]);

        $service = new ConversationService();
        $service->handleIncomingMessage($conversation);

        $this->assertEquals('open', $conversation->fresh()->status);
        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_close_conversation()
    {
        $contact = Contact::factory()->create();
        $conversation = Conversation::create([
            'team_id' => $contact->team_id,
            'contact_id' => $contact->id,
            'status' => 'open'
        ]);

        $service = new ConversationService();
        $service->close($conversation);

        $this->assertEquals('closed', $conversation->fresh()->status);
        $this->assertNotNull($conversation->fresh()->closed_at);
    }
}
