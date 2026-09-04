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
            'name' => 'John API',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'embed_url']);

        $token = $response->json('token');
        $service = new EmbedTokenService;
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
            'permissions' => ['read'],
        ]);

        $token = $response->json('token');
        $service = new EmbedTokenService;
        $payload = $service->validateToken($token);

        $this->assertEquals(['read'], $payload['permissions']);
    }

    public function test_embed_view_loads_with_valid_token()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        $service = new EmbedTokenService;
        $token = $service->generateToken($contact);

        $response = $this->get('/embed/chat?token='.$token);
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

    public function test_api_read_conversation_returns_latest_conversation()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+19876543210']);

        // Older conversation
        $oldConv = Conversation::factory()->create([
            'contact_id' => $contact->id,
            'team_id' => $team->id,
            'last_message_at' => now()->subDays(5),
        ]);
        Message::factory()->create(['conversation_id' => $oldConv->id, 'content' => 'Old message']);

        // Newer conversation
        $newConv = Conversation::factory()->create([
            'contact_id' => $contact->id,
            'team_id' => $team->id,
            'last_message_at' => now(),
        ]);
        Message::factory()->create(['conversation_id' => $newConv->id, 'content' => 'Latest message']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/conversations/+19876543210');
        $response->assertStatus(200)
            ->assertJsonFragment(['content' => 'Latest message'])
            ->assertJsonMissing(['content' => 'Old message']);
    }

    public function test_api_store_contact_preserves_existing_custom_attributes_and_email()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $user->teams()->attach($team, ['role' => 'admin']);

        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'phone_number' => '+19876543211',
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'custom_attributes' => ['vip' => true, 'segment' => 'retail'],
        ]);

        Sanctum::actingAs($user);

        // Update name without passing email or custom_attributes
        $response = $this->postJson('/api/v1/contacts', [
            'phone_number' => '+19876543211',
            'name' => 'Updated Name',
        ], ['X-Tenant-ID' => $team->id]);

        $response->assertStatus(201);
        $contact->refresh();
        $this->assertEquals('Updated Name', $contact->name);
        $this->assertEquals('original@example.com', $contact->email);
        $this->assertEquals(['vip' => true, 'segment' => 'retail'], $contact->custom_attributes);
    }

    public function test_api_otp_verify_enforces_tenant_isolation()
    {
        $teamA = Team::factory()->create();
        $userA = User::factory()->create(['current_team_id' => $teamA->id]);
        $userA->teams()->attach($teamA, ['role' => 'admin']);

        $teamB = Team::factory()->create();

        // Seed OTP cache for Team B
        \Illuminate\Support\Facades\Cache::put('otp_+19876543212', [
            'hash' => \Illuminate\Support\Facades\Hash::make('654321'),
            'attempts' => 0,
            'team_id' => $teamB->id,
            'type' => 'phone',
        ], 300);

        Sanctum::actingAs($userA);

        // User from Team A tries to verify Team B's OTP
        $response = $this->postJson('/api/v1/otp/verify', [
            'phone' => '+19876543212',
            'code' => '654321',
        ], ['X-Tenant-ID' => $teamA->id]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'ERR_OTP_INVALID');
    }
}
