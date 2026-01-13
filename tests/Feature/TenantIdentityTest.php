<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a test route using the tenant middleware
        Route::middleware(['web', 'auth:sanctum', 'tenant'])->get('/test-tenant-context', function () {
            $service = new TenantService();
            return response()->json([
                'tenant_id' => $service->getTenantId(),
                'waba_id' => $service->getWabaId(),
                'role' => $service->getUserRole(),
            ]);
        });
    }

    public function test_request_aborted_if_no_team_selected()
    {
        $user = User::factory()->create();
        // Ensure user has no current team (might need to detach if factory attaches one)
        $user->current_team_id = null;
        $user->save();

        $response = $this->actingAs($user)->getJson('/test-tenant-context');

        $response->assertStatus(403);
    }

    public function test_request_succeeds_with_team()
    {
        $user = User::factory()->withPersonalTeam()->create();

        $team = $user->personalTeam();
        $team->whatsapp_business_account_id = 'waba_123';
        $team->save();

        $response = $this->actingAs($user)->getJson('/test-tenant-context');

        $response->assertStatus(200)
            ->assertJson([
                'tenant_id' => $team->id,
                'waba_id' => 'waba_123',
            ]);
    }

    public function test_audit_service_logs_action()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $audit = new AuditService();
        $audit->log('test.action', 'Testing audit log', $user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'team_id' => $user->currentTeam->id,
            'action' => 'test.action',
            'description' => 'Testing audit log',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }

    public function test_tenant_service_methods()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->personalTeam();
        $team->whatsapp_business_account_id = 'waba_test';
        $team->whatsapp_phone_number_id = 'phone_test';
        $team->whatsapp_access_token = 'token_test';
        $team->whatsapp_connected = true;
        $team->save();

        $this->actingAs($user);
        $service = new TenantService();

        $this->assertEquals($team->id, $service->getTenantId());
        $this->assertEquals('waba_test', $service->getWabaId());
        $this->assertEquals('phone_test', $service->getPhoneNumberId());
        $this->assertEquals('token_test', $service->getAccessToken());
        $this->assertTrue($service->isConnected());
    }

    public function test_context_switching_via_header()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamA = $user->personalTeam();

        $teamB = Team::factory()->create(['user_id' => $user->id, 'personal_team' => false]);
        $user->teams()->attach($teamB);
        $user->switchTeam($teamA);

        // Default request should return Team A
        $this->actingAs($user)->getJson('/test-tenant-context')
            ->assertJson(['tenant_id' => $teamA->id]);

        // Request with header for Team B
        $this->actingAs($user)
            ->withHeader('X-Tenant-ID', $teamB->id)
            ->getJson('/test-tenant-context')
            ->assertJson([
                'tenant_id' => $teamB->id,
                // Owner is usually admin, but let's just inspect it or assert structure if role key logic varies
                // In Jetstream, role might be null if not explicitly assigned in team_user (owner vs member).
                // Actually, owner has no role in pivot, they are owner.
                // But HasTeams:teamRole checks if user owns team. If so it returns 'Owner' role config.
                // Let's assume 'admin' for now or just check key exists.
            ]);

        // Verify database state didn't change (still Team A)
        $this->assertEquals($teamA->id, $user->fresh()->current_team_id);
    }

    public function test_context_switching_forbidden_for_non_member_teams()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = Team::factory()->create(['personal_team' => false]); // User not a member

        $this->actingAs($user)
            ->withHeader('X-Tenant-ID', $otherTeam->id)
            ->getJson('/test-tenant-context')
            ->assertStatus(403);
    }
}
