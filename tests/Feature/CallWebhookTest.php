<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class CallWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $team;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create([
            'phone_number_id' => '1234567890',
        ]);

        $this->team->users()->attach($this->user, ['role' => 'admin']);
    }

    /** @test */
    public function it_handles_incoming_call_webhook()
    {
        Event::fake();

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => $this->team->phone_number_id,
                                ],
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'from' => '1234567890',
                                        'timestamp' => now()->timestamp,
                                        'status' => 'ringing',
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('whatsapp_calls', [
            'call_id' => 'call_123',
            'from_number' => '1234567890',
            'status' => 'ringing',
            'direction' => 'inbound',
        ]);
    }

    /** @test */
    public function it_handles_call_answered_webhook()
    {
        $call = WhatsAppCall::factory()->create([
            'team_id' => $this->team->id,
            'call_id' => 'call_123',
            'status' => 'ringing',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'status' => 'in_progress',
                                        'timestamp' => now()->timestamp,
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload);

        $response->assertStatus(200);

        $call->refresh();
        $this->assertEquals('in_progress', $call->status);
        $this->assertNotNull($call->answered_at);
    }

    /** @test */
    public function it_handles_call_ended_webhook()
    {
        $call = WhatsAppCall::factory()->create([
            'team_id' => $this->team->id,
            'call_id' => 'call_123',
            'status' => 'in_progress',
            'answered_at' => now()->subMinutes(5),
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'status' => 'completed',
                                        'duration_seconds' => 300,
                                        'timestamp' => now()->timestamp,
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload);

        $response->assertStatus(200);

        $call->refresh();
        $this->assertEquals('completed', $call->status);
        $this->assertEquals(300, $call->duration_seconds);
        $this->assertNotNull($call->ended_at);
    }

    /** @test */
    public function it_calculates_call_cost_on_completion()
    {
        $call = WhatsAppCall::factory()->create([
            'team_id' => $this->team->id,
            'call_id' => 'call_123',
            'status' => 'in_progress',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'status' => 'completed',
                                        'duration_seconds' => 300, // 5 minutes
                                        'timestamp' => now()->timestamp,
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload);

        $call->refresh();
        $this->assertGreaterThan(0, $call->cost_amount);
        // Assuming $0.005 per minute: 5 minutes * $0.005 = $0.025
        $this->assertEquals(0.025, $call->cost_amount);
    }

    /** @test */
    public function it_verifies_webhook_challenge()
    {
        $response = $this->get('/api/webhook/whatsapp/calls?hub.mode=subscribe&hub.verify_token=test_token&hub.challenge=challenge_123');

        $response->assertStatus(200);
        $response->assertSee('challenge_123');
    }
}
