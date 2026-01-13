<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebhookComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_handling_stop_keyword_opts_out_contact()
    {
        Event::fake(); // Prevent actual broadcasting

        // 1. Setup Data
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id, 'whatsapp_phone_number_id' => '123456789']);
        $user->switchTeam($team);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => '15550000000',
            'name' => 'Tester',
            'opt_in_status' => 'opted_in', // Already opted in
        ]);

        // 2. Simulate Webhook Payload for "STOP" message
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => ['phone_number_id' => '123456789'],
                                'messages' => [
                                    [
                                        'from' => '15550000000',
                                        'id' => 'wamid.HBgLMTU1NTAwMDAwMDAVAgASGBQ...',
                                        'type' => 'text',
                                        'text' => ['body' => 'STOP'],
                                        'timestamp' => time(),
                                    ]
                                ],
                                'contacts' => [['profile' => ['name' => 'Tester']]]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // 3. Send Webhook Request
        $this->postJson('/api/webhook/whatsapp', $payload);

        // 4. Assertions
        $contact->refresh();
        $this->assertEquals('opted_out', $contact->opt_in_status);

        $this->assertDatabaseHas('consent_logs', [
            'contact_id' => $contact->id,
            'action' => 'OPT_OUT',
            'source' => 'STOP_KEYWORD',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'STOP',
            'direction' => 'inbound',
        ]);
    }
}
