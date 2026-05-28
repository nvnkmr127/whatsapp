<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_conversations_with_last_messages()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);

        $message = Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
        ]);

        $response = $this->getJson('/api/v1/mobile/conversations', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $conversation->id);
    }

    public function test_can_send_media_message()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);

        // Create inbound message within 24h
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'created_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/mobile/conversations/{$conversation->id}/messages", [
            'type' => 'image',
            'content' => '',
            'media_url' => 'https://example.com/image.jpg',
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
    }

    public function test_can_search_conversations_by_query_parameter()
    {
        $contact1 = Contact::factory()->create(['team_id' => $this->team->id, 'name' => 'Alice Watson']);
        $conversation1 = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact1->id,
            'last_message_at' => now(),
        ]);

        $contact2 = Contact::factory()->create(['team_id' => $this->team->id, 'name' => 'Bob Smith']);
        $conversation2 = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact2->id,
            'last_message_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/api/v1/mobile/conversations?query=Alice', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conversation1->id);
    }

    public function test_can_search_conversations_by_search_parameter()
    {
        $contact1 = Contact::factory()->create(['team_id' => $this->team->id, 'name' => 'Alice Watson']);
        $conversation1 = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact1->id,
            'last_message_at' => now(),
        ]);

        $contact2 = Contact::factory()->create(['team_id' => $this->team->id, 'name' => 'Bob Smith']);
        $conversation2 = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact2->id,
            'last_message_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/api/v1/mobile/conversations?search=Bob', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conversation2->id);
    }

    public function test_can_star_message()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);
        $message = Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'is_starred' => false,
        ]);

        $response = $this->postJson("/api/v1/mobile/messages/{$message->id}/star", [], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_starred' => true]);
        $this->assertTrue($message->fresh()->is_starred);
    }

    public function test_can_list_starred_messages()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);
        
        $starredMessage1 = Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'is_starred' => true,
            'content' => 'Starred message 1',
        ]);
        $starredMessage2 = Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'is_starred' => true,
            'content' => 'Starred message 2',
        ]);
        $unstarredMessage = Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'is_starred' => false,
            'content' => 'Unstarred message',
        ]);

        $response = $this->getJson("/api/v1/mobile/messages/starred", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data',
            'last_page',
            'total',
        ]);
        
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $starredMessage2->id); // Descending order
        $response->assertJsonPath('data.1.id', $starredMessage1->id);
        $response->assertJsonPath('data.0.contact.name', $contact->name);
        $response->assertJsonPath('data.0.conversation.id', $conversation->id);
    }


    public function test_can_forward_message()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation1 = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);
        $conversation2 = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);
        $message = Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'conversation_id' => $conversation1->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'Hello forwarding world',
        ]);

        $response = $this->postJson("/api/v1/mobile/messages/{$message->id}/forward", [
            'to_conversation_id' => $conversation2->id
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation2->id,
            'direction' => 'outbound',
            'content' => 'Hello forwarding world',
        ]);
    }

    public function test_can_get_conversation_notes()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);

        $note = \App\Models\InternalNote::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'conversation_id' => $conversation->id,
            'content' => 'This is a test note content.',
        ]);

        $response = $this->getJson("/api/v1/mobile/conversations/{$conversation->id}/notes", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $note->id,
            'content' => 'This is a test note content.',
        ]);
    }

    public function test_can_store_conversation_note()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->postJson("/api/v1/mobile/conversations/{$conversation->id}/notes", [
            'content' => 'Another brand new note'
        ], [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'content' => 'Another brand new note',
        ]);

        $this->assertDatabaseHas('internal_notes', [
            'conversation_id' => $conversation->id,
            'content' => 'Another brand new note',
        ]);
    }
}

