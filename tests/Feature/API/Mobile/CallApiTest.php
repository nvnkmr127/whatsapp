<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CallApiTest extends TestCase
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

    public function test_can_get_contact_call_history()
    {
        $contact = Contact::factory()->create(['team_id' => $this->team->id]);

        $call = WhatsAppCall::create([
            'team_id' => $this->team->id,
            'contact_id' => $contact->id,
            'call_id' => 'call-123456',
            'direction' => 'outbound',
            'status' => 'completed',
            'from_number' => '+14155550118',
            'to_number' => $contact->phone_number ?? '+14155550119',
            'duration_seconds' => 45,
            'initiated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/calls/contacts/{$contact->id}/history", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.0.id', $call->id);
        $response->assertJsonPath('data.0.call_id', 'call-123456');
    }
}
