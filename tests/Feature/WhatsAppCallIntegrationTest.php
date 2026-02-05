<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\CallOffered;
use App\Events\CallAnswered;
use App\Events\CallRejected;

class WhatsAppCallIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $team;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable signature verification for tests
        config(['whatsapp.app_secret' => null]);

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create([
            'whatsapp_phone_number_id' => '1234567890', // Ensure this matches logic in job
        ]);
    }

    /** @test */
    public function it_handles_incoming_call_with_sdp_offer()
    {
        Event::fake();

        $callId = 'wacid.call_offer_' . rand(1000, 9999);
        $payload = [
            'entry' => [
                [
                    'id' => $this->team->whatsapp_business_account_id ?? 'waba_id',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => $this->team->whatsapp_phone_number_id,
                                    'display_phone_number' => '15550000000',
                                ],
                                'calls' => [
                                    [
                                        'id' => $callId,
                                        'from' => '15551234567',
                                        'to' => '15550000000',
                                        'timestamp' => now()->timestamp,
                                        'event' => 'connect', // Crucial: event is connect for offer
                                        'direction' => 'USER_INITIATED',
                                        'session' => [
                                            'sdp_type' => 'offer',
                                            'sdp' => "v=0\r\no=- 1234 2 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111\r\nc=IN IP4 0.0.0.0\r\na=rtpmap:111 opus/48000/2\r\n",
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'calls',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload, [
            'X-Hub-Signature-256' => 'sha256=dummy_signature'
        ]);

        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('whatsapp_calls', [
            'call_id' => $callId,
            'from_number' => '15551234567',
            'status' => 'initiated', // Initial status on connect
            'direction' => 'inbound',
        ]);

        $call = WhatsAppCall::where('call_id', $callId)->first();
        // $this->assertNotNull($call->metadata['sdp']);
        // $this->assertStringContainsString('v=0', $call->metadata['sdp']);

        Event::assertDispatched(CallOffered::class);
    }

    /** @test */
    public function it_handles_call_rejection()
    {
        Event::fake();

        $callId = 'wacid.call_reject_' . rand(1000, 9999);
        $call = WhatsAppCall::factory()->create([
            'team_id' => $this->team->id,
            'call_id' => $callId,
            'status' => 'ringing',
            'direction' => 'inbound',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => $this->team->whatsapp_business_account_id ?? 'waba_id',
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $this->team->whatsapp_phone_number_id,
                                ],
                                'calls' => [
                                    [
                                        'id' => $callId,
                                        'event' => 'terminate',
                                        'status' => 'rejected', // Rejected by user
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

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload, [
            'X-Hub-Signature-256' => 'sha256=dummy_signature'
        ]);
        $response->assertJson(['status' => 'success']);

        $this->assertEquals(1, WhatsAppCall::count(), 'Should have only 1 call record');

        $call->refresh();
        $this->assertEquals('rejected', $call->status);
        $this->assertNotNull($call->ended_at);

        Event::assertDispatched(CallRejected::class);
    }

    /** @test */
    public function it_handles_outbound_call_answer_with_sdp()
    {
        Event::fake();

        $callId = 'wacid.outbound_' . rand(1000, 9999);
        $call = WhatsAppCall::factory()->create([
            'team_id' => $this->team->id,
            'call_id' => $callId,
            'status' => 'initiated',
            'direction' => 'outbound',
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $this->team->whatsapp_phone_number_id,
                                ],
                                'calls' => [
                                    [
                                        'id' => $callId,
                                        'event' => 'connect', // Outbound answer comes as connect event too
                                        'direction' => 'BUSINESS_INITIATED',
                                        'session' => [
                                            'sdp_type' => 'answer',
                                            'sdp' => "v=0\r\n...",
                                        ],
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

        $response = $this->postJson('/api/webhook/whatsapp/calls', $payload, [
            'X-Hub-Signature-256' => 'sha256=dummy_signature'
        ]);
        $response->assertJson(['status' => 'success']);

        $call->refresh();
        $this->assertEquals('in_progress', $call->status); // Should be in_progress after answer
        $this->assertNotNull($call->metadata['answered_sdp']);

        Event::assertDispatched(CallAnswered::class); // Or CallConnected
    }
}
