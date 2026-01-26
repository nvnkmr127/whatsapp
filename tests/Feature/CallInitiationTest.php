<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Contact;
use App\Models\WhatsAppCall;
use App\Services\CallService;
use App\Services\CallEligibilityService;
use App\Services\CallConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CallInitiationTest extends TestCase
{
    use RefreshDatabase;

    protected $team;
    protected $user;
    protected $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create([
            'calling_enabled' => true,
            'max_call_minutes_per_month' => 1000,
        ]);

        $this->team->users()->attach($this->user, ['role' => 'admin']);
        $this->user->current_team_id = $this->team->id;
        $this->user->save();

        $this->contact = Contact::factory()->create([
            'team_id' => $this->team->id,
            'opt_in_status' => 'opted_in',
        ]);
    }

    /** @test */
    public function it_can_initiate_call_with_valid_eligibility()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/calls/initiate', [
            'phone_number' => $this->contact->phone_number,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_calls', [
            'team_id' => $this->team->id,
            'to_number' => $this->contact->phone_number,
            'direction' => 'outbound',
        ]);
    }

    /** @test */
    public function it_blocks_call_when_calling_disabled()
    {
        $this->team->update(['calling_enabled' => false]);
        $this->actingAs($this->user);

        $response = $this->postJson('/api/calls/initiate', [
            'phone_number' => $this->contact->phone_number,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /** @test */
    public function it_blocks_call_when_contact_opted_out()
    {
        $this->contact->update(['opt_in_status' => 'opted_out']);
        $this->actingAs($this->user);

        $response = $this->postJson('/api/calls/initiate', [
            'phone_number' => $this->contact->phone_number,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('block_reason', 'CONTACT_OPTED_OUT');
    }

    /** @test */
    public function it_blocks_call_when_monthly_limit_reached()
    {
        $this->team->update(['max_call_minutes_per_month' => 10]);

        // Create calls that exceed the limit
        WhatsAppCall::factory()->create([
            'team_id' => $this->team->id,
            'duration_seconds' => 600, // 10 minutes
            'created_at' => now(),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/calls/initiate', [
            'phone_number' => $this->contact->phone_number,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('block_reason', 'MONTHLY_LIMIT_REACHED');
    }

    /** @test */
    public function it_validates_user_initiated_trigger()
    {
        $consentService = new CallConsentService($this->team);

        $result = $consentService->validateCallTrigger(
            $this->contact,
            'user_initiated',
            [
                'trigger_source' => 'message_keyword',
                'trigger_message' => 'call me please',
            ]
        );

        $this->assertTrue($result['allowed']);
        $this->assertEquals('implicit', $result['consent_type']);
    }

    /** @test */
    public function it_requires_explicit_consent_for_agent_offered()
    {
        $consentService = new CallConsentService($this->team);

        // Without user response
        $result = $consentService->validateCallTrigger(
            $this->contact,
            'agent_offered',
            [
                'offer_sent_at' => now()->subMinutes(5),
            ]
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('NO_EXPLICIT_CONSENT', $result['block_reason']);

        // With user response
        $result = $consentService->validateCallTrigger(
            $this->contact,
            'agent_offered',
            [
                'offer_sent_at' => now()->subMinutes(5),
                'user_response' => 'yes',
                'user_response_at' => now()->subMinutes(4),
            ]
        );

        $this->assertTrue($result['allowed']);
        $this->assertEquals('explicit', $result['consent_type']);
    }

    /** @test */
    public function it_blocks_user_initiated_without_keywords()
    {
        $consentService = new CallConsentService($this->team);

        $result = $consentService->validateCallTrigger(
            $this->contact,
            'user_initiated',
            [
                'trigger_source' => 'message_keyword',
                'trigger_message' => 'Hello there',
            ]
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('NO_CALL_KEYWORD_DETECTED', $result['block_reason']);
    }

    /** @test */
    public function it_blocks_agent_offered_with_negative_response()
    {
        $consentService = new CallConsentService($this->team);

        $result = $consentService->validateCallTrigger(
            $this->contact,
            'agent_offered',
            [
                'offer_sent_at' => now()->subMinutes(5),
                'user_response' => 'No thanks',
                'user_response_at' => now()->subMinutes(4),
            ]
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('NO_EXPLICIT_CONSENT', $result['block_reason']);
    }

    /** @test */
    public function it_blocks_agent_offered_with_expired_consent()
    {
        $consentService = new CallConsentService($this->team);

        $result = $consentService->validateCallTrigger(
            $this->contact,
            'agent_offered',
            [
                'offer_sent_at' => now()->subMinutes(70),
                'user_response' => 'yes',
                'user_response_at' => now()->subMinutes(65),
            ]
        );

        $this->assertFalse($result['allowed']);
        $this->assertEquals('CONSENT_EXPIRED', $result['block_reason']);
    }

    /** @test */
    public function it_detects_various_affirmative_responses()
    {
        $this->assertTrue(CallConsentService::isAffirmative('Yes'));
        $this->assertTrue(CallConsentService::isAffirmative('ok'));
        $this->assertTrue(CallConsentService::isAffirmative('Sure thing'));
        $this->assertTrue(CallConsentService::isAffirmative('Yeah, go ahead'));
        $this->assertFalse(CallConsentService::isAffirmative('Not now'));
        $this->assertFalse(CallConsentService::isAffirmative('Tell me more'));
    }

    /** @test */
    public function it_detects_call_request_keywords()
    {
        $this->assertTrue(CallConsentService::detectCallRequest('Can you call me?'));
        $this->assertTrue(CallConsentService::detectCallRequest('I need a call please'));
        $this->assertTrue(CallConsentService::detectCallRequest('Please give me a call'));
        $this->assertFalse(CallConsentService::detectCallRequest('Send me a message'));
    }

    /** @test */
    public function it_logs_consent_to_audit_trail()
    {
        $consentService = new CallConsentService($this->team);

        $validationResult = [
            'allowed' => true,
            'consent_type' => 'explicit',
            'checks' => [
                'consent' => [
                    'details' => [
                        'consent_valid_until' => now()->addHour(),
                    ],
                ],
                'window' => [
                    'within_window' => true,
                ],
                'context' => [
                    'details' => [
                        'is_highly_active' => true,
                    ],
                ],
            ],
        ];

        $consentService->logConsent(
            $this->contact,
            'user_initiated',
            ['trigger_source' => 'button_click'],
            $validationResult
        );

        $this->assertDatabaseHas('calling_consent_log', [
            'team_id' => $this->team->id,
            'contact_id' => $this->contact->id,
            'consent_type' => 'explicit',
            'trigger_type' => 'user_initiated',
        ]);
    }

    /** @test */
    public function it_checks_eligibility_with_all_validations()
    {
        $eligibilityService = new CallEligibilityService($this->team);

        $result = $eligibilityService->checkEligibility(
            $this->contact,
            'user_initiated',
            [
                'trigger_source' => 'in_app_action',
                'trigger_message' => 'User clicked call button',
            ]
        );

        $this->assertTrue($result['eligible']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('trigger_consent', $result['checks']);
        $this->assertArrayHasKey('phone_readiness', $result['checks']);
        $this->assertArrayHasKey('quality_rating', $result['checks']);
    }
}
