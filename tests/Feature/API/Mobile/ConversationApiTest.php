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
}
