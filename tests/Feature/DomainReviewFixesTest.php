<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Team;
use App\Models\TeamWallet;
use App\Models\Workflow;
use App\Services\BroadcastService;
use App\Services\ConsentService;
use App\Services\OutboundPreflightService;
use App\Services\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DomainReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_overage_is_allowed_if_cost_is_above_zero()
    {
        config(['app.full_access_all' => false]);
        $team = Team::factory()->create();

        // Create real Entitlement object (DHO)
        $e = new \App\Services\Entitlement(
            teamId: $team->id,
            planName: 'test',
            status: 'active',
            active: true,
            onTrial: false,
            inGrace: false,
            expired: false,
            offerEligible: false,
            trialDaysRemaining: 0,
            trialEndsAt: null,
            features: ['campaigns' => true],
            limits: ['message_limit' => 500],
            usage: ['messages_this_month' => 0]
        );

        // Mock EntitlementService to return our $e
        $mockSvc = \Mockery::mock(\App\Services\EntitlementService::class);
        $mockSvc->shouldReceive('for')->with($team)->andReturn($e);
        app()->instance(\App\Services\EntitlementService::class, $mockSvc);

        $service = app(OutboundPreflightService::class);

        // 1. FREE message (cost=0) should be BLOCKED if over limit
        // Current usage (1000) > limit (500)
        $result = $service->authorize($team, 'campaigns', 'message_limit', 1000, 0.00);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('ERR_QUOTA_EXCEEDED', $result['code']);

        // 2. PAID message (cost>0) should be ALLOWED even if over limit (if balance exists)
        TeamWallet::updateOrCreate(['team_id' => $team->id], ['balance' => 10.00]);
        $result = $service->authorize($team, 'send_message', 'message_limit', 1000, 0.10);
        $this->assertTrue($result['allowed']);
    }

    public function test_workflow_recursion_limit()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id]);

        // Workflow that triggers itself (Recursion)
        Workflow::create([
            'team_id' => $team->id,
            'name' => 'Infinite Loop',
            'trigger_type' => 'tag_added',
            'is_active' => true,
            'definition' => [['action_type' => 'add_tag', 'config' => ['tag_id' => 1]]],
        ]);

        $engine = app(WorkflowEngine::class);

        // This stops at depth 4. We ensure it returns gracefully.
        $engine->trigger('tag_added', $contact, ['tag_id' => 1]);

        $this->assertTrue(true); // Didn't crash
    }

    public function test_start_keyword_overrides_admin_opt_out()
    {
        $team = Team::factory()->create();
        $contact = Contact::factory()->create([
            'team_id' => $team->id,
            'opt_in_status' => 'opted_out',
        ]);

        // Log an admin opt-out
        \App\Models\ConsentLog::create([
            'team_id' => $team->id,
            'contact_id' => $contact->id,
            'action' => 'OPT_OUT',
            'source' => 'MANUAL_ADMIN',
        ]);

        $service = app(ConsentService::class);

        // Try to opt-in via generic method (API) -> should fail
        $res = $service->optIn($contact, 'API');
        $this->assertFalse($res);
        $this->assertEquals('opted_out', $contact->fresh()->opt_in_status);

        // Try to opt-in via START keyword -> should succeed
        $res = $service->optIn($contact, 'START_KEYWORD');
        $this->assertTrue($res);
        $this->assertEquals('opted_in', $contact->fresh()->opt_in_status);
    }

    public function test_broadcast_launch_fails_if_zero_contacts()
    {
        $team = Team::factory()->create();
        $campaign = Campaign::create([
            'team_id' => $team->id,
            'name' => 'Empty Campaign',
            'total_contacts' => 0,
        ]);

        $service = app(BroadcastService::class);
        $res = $service->launch($campaign);

        $this->assertNull($res);
        $this->assertEquals('failed', $campaign->fresh()->status);
        $this->assertStringContainsString('no contacts', $campaign->fresh()->error_message);
    }
}
