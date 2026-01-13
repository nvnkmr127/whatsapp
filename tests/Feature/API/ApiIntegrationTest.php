<?php

namespace Tests\Feature\API;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use App\Services\EmbedTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_embed_token()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/embed-token', [
            'phone_number' => '1234567890',
            'name' => 'John API'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'embed_url']);

        $token = $response->json('token');
        $service = new EmbedTokenService();
        $payload = $service->validateToken($token);

        $this->assertNotNull($payload);
        $this->assertEquals($team->id, $payload['team_id']);
        // Default permissions
        $this->assertEquals(['read', 'write'], $payload['permissions']);
    }

    public function test_generate_embed_token_with_permissions()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/embed-token', [
            'phone_number' => '1234567890',
            'permissions' => ['read']
        ]);

        $token = $response->json('token');
        $service = new EmbedTokenService();
        $payload = $service->validateToken($token);

        $this->assertEquals(['read'], $payload['permissions']);
    }

    public function test_embed_view_loads_with_valid_token()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        $service = new EmbedTokenService();
        $token = $service->generateToken($contact);

        $response = $this->get('/embed/chat?token=' . $token);
        $response->assertStatus(200);
        $response->assertSeeLivewire('chat.embedded-chat');
    }

    public function test_api_read_conversation()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '12345']);
        $conversation = Conversation::factory()->create(['contact_id' => $contact->id, 'team_id' => $team->id]);
        Message::factory()->create(['conversation_id' => $conversation->id, 'content' => 'Hello API']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/conversations/12345');

        $response->assertStatus(200)
            ->assertJsonFragment(['content' => 'Hello API']);
    }
}
