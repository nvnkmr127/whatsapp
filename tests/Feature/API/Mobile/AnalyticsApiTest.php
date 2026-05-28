<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $contact;
    protected $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user->refresh();

        Sanctum::actingAs($this->user);

        $this->contact = Contact::factory()->create(['team_id' => $this->team->id]);
        $this->conversation = Conversation::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
        ]);
    }

    public function test_dashboard_defaults_to_30_days_range()
    {
        // Message 5 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'read',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Message 20 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'delivered',
            'created_at' => Carbon::now()->subDays(20),
        ]);

        // Message 50 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'delivered',
            'created_at' => Carbon::now()->subDays(50),
        ]);

        $response = $this->getJson('/api/v1/mobile/analytics/dashboard', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('messages_30d.sent', 2);
    }

    public function test_dashboard_applies_7_days_range()
    {
        // Message 5 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'read',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Message 20 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'delivered',
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $response = $this->getJson('/api/v1/mobile/analytics/dashboard?range=7d', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('messages_30d.sent', 1);
    }

    public function test_dashboard_applies_90_days_range()
    {
        // Message 5 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'read',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Message 50 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'delivered',
            'created_at' => Carbon::now()->subDays(50),
        ]);

        // Message 100 days ago
        Message::factory()->create([
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
            'status' => 'delivered',
            'created_at' => Carbon::now()->subDays(100),
        ]);

        $response = $this->getJson('/api/v1/mobile/analytics/dashboard?range=90d', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('messages_30d.sent', 2);
    }
}
