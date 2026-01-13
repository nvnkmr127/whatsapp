<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PolicyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_send_free_text_outside_24h_window()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id, 'whatsapp_phone_number_id' => '123', 'whatsapp_access_token' => 'abc']);
        $user->switchTeam($team);

        // Contact with old interaction
        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => '15550000000',
            'name' => 'Old Contact',
            'last_interaction_at' => now()->subHours(25),
        ]);

        $this->actingAs($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('24-hour window is closed');

        $service = new WhatsAppService();
        $service->setTeam($team)->sendText('15550000000', 'Hello');
    }

    public function test_can_send_free_text_inside_24h_window()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id, 'whatsapp_phone_number_id' => '123', 'whatsapp_access_token' => 'abc']);
        $user->switchTeam($team);

        // Contact with recent interaction
        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => '15550000000',
            'name' => 'Active Contact',
            'last_interaction_at' => now()->subMinutes(10),
        ]);

        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $this->actingAs($user);

        $service = new WhatsAppService();
        $result = $service->setTeam($team)->sendText('15550000000', 'Hello');

        $this->assertTrue($result['success']);
    }
}
