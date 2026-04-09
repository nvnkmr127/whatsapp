<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

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
            'whatsapp_phone_number_id' => '1234567890',
        ]);

        $this->team->users()->attach($this->user, ['role' => 'admin']);
    }

    protected function postWebhook(array $payload)
    {
        config(['whatsapp.app_secret' => 'test_secret']);

        // Use post() instead of postJson() because postJson() re-encodes, and we want to ensure
        // the payload and the signature match exactly. Or, we can just use json_encode here
        // and hope Laravel's postJson encodes it exactly the same. Usually it does.
        $content = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $content, 'test_secret');

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
        ])->postJson('/api/webhook/whatsapp/calls', $payload);

        if ($response->status() !== 200) {
            dump($response->content());
        }

        return $response;
    }

    /** @test */
    public function it_handles_incoming_call_webhook()
    {
        Event::fake();

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->whatsapp_phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => $this->team->whatsapp_phone_number_id,
                                ],
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'from' => [
                                            'display_phone_number' => '1234567890',
                                        ],
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

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('whatsapp_calls', [
            'call_id' => 'call_123',
            'from_number' => '1234567890',
            'status' => 'ringing',
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
                    'id' => $this->team->whatsapp_phone_number_id,
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

        $response = $this->postWebhook($payload);

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
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->whatsapp_phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'status' => 'completed',
                                        'timestamp' => now()->timestamp,
                                        'duration' => 300,
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postWebhook($payload);

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
            'answered_at' => now()->subMinutes(5),
            'direction' => 'outbound', // Cost usually depends on outbound
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->whatsapp_phone_number_id,
                    'changes' => [
                        [
                            'value' => [
                                'calls' => [
                                    [
                                        'call_id' => 'call_123',
                                        'status' => 'completed',
                                        'timestamp' => now()->timestamp,
                                        'duration' => 300,
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postWebhook($payload);

        $call->refresh();
        $this->assertGreaterThan(0, $call->cost_amount);
        // Assuming $0.005 per minute: 5 minutes * $0.005 = $0.025
        $this->assertEquals(0.025, $call->cost_amount);
    }

    /** @test */
    public function it_verifies_webhook_challenge()
    {
        config(['whatsapp.webhook_verify_token' => 'test_token']);
        $response = $this->get('/api/webhook/whatsapp/calls?hub_mode=subscribe&hub_verify_token=test_token&hub_challenge=challenge_123');

        $response->assertStatus(200);
        $response->assertSee('challenge_123');
    }
}
