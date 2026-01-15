<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that super admin can access tenant creation page.
     */
    public function test_super_admin_can_access_tenant_creation_page(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.tenants.create'));

        $response->assertStatus(200);
        $response->assertSee('Company Details');
        $response->assertSee('Owner Account');
    }

    /**
     * Test that non-super admin cannot access tenant creation page.
     */
    public function test_non_super_admin_cannot_access_tenant_creation_page(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.tenants.create'));

        $response->assertStatus(403);
    }

    /**
     * Test successful tenant creation with valid data.
     */
    public function test_successful_tenant_creation_with_valid_data(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $tenantData = [
            'company_name' => 'Test Company',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testcompany.com',
            'owner_password' => 'password123',
            'plan' => 'pro',
        ];

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), $tenantData);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('flash.banner');
        $response->assertSessionHas('flash.bannerStyle', 'success');

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'owner@testcompany.com',
            'name' => 'Test Owner',
        ]);

        // Verify team was created
        $this->assertDatabaseHas('teams', [
            'name' => 'Test Company',
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
            'personal_team' => false,
        ]);

        // Verify user is attached to team
        $user = User::where('email', 'owner@testcompany.com')->first();
        $team = Team::where('name', 'Test Company')->first();

        $this->assertTrue($user->belongsToTeam($team));
        $this->assertEquals($team->id, $user->current_team_id);
    }

    /**
     * Test validation errors with invalid data.
     */
    public function test_validation_errors_with_invalid_data(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        // Test with missing required fields
        $response = $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), []);

        $response->assertSessionHasErrors([
            'company_name',
            'owner_name',
            'owner_email',
            'owner_password',
            'plan',
        ]);

        // Test with invalid email
        $response = $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), [
                'company_name' => 'Test Company',
                'owner_name' => 'Test Owner',
                'owner_email' => 'invalid-email',
                'owner_password' => 'password123',
                'plan' => 'pro',
            ]);

        $response->assertSessionHasErrors(['owner_email']);

        // Test with short password
        $response = $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), [
                'company_name' => 'Test Company',
                'owner_name' => 'Test Owner',
                'owner_email' => 'owner@test.com',
                'owner_password' => 'short',
                'plan' => 'pro',
            ]);

        $response->assertSessionHasErrors(['owner_password']);
    }

    /**
     * Test unique email constraint.
     */
    public function test_unique_email_constraint(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        // Create a user with an email
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Try to create tenant with same email
        $response = $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), [
                'company_name' => 'Test Company',
                'owner_name' => 'Test Owner',
                'owner_email' => 'existing@example.com',
                'owner_password' => 'password123',
                'plan' => 'pro',
            ]);

        $response->assertSessionHasErrors(['owner_email']);
    }

    /**
     * Test that tenant creation logs are created.
     */
    public function test_tenant_creation_creates_logs(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'email' => 'admin@example.com',
        ]);

        $tenantData = [
            'company_name' => 'Test Company',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testcompany.com',
            'owner_password' => 'password123',
            'plan' => 'enterprise',
        ];

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('Tenant created', \Mockery::on(function ($context) {
                return isset($context['team_id'])
                    && isset($context['team_name'])
                    && isset($context['owner_email'])
                    && isset($context['plan'])
                    && isset($context['created_by']);
            }));

        $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), $tenantData);
    }

    /**
     * Test that subscription ends at is set correctly.
     */
    public function test_subscription_ends_at_is_set(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $tenantData = [
            'company_name' => 'Test Company',
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@testcompany.com',
            'owner_password' => 'password123',
            'plan' => 'basic',
        ];

        $this->actingAs($superAdmin)
            ->post(route('admin.tenants.store'), $tenantData);

        $team = Team::where('name', 'Test Company')->first();

        $this->assertNotNull($team->subscription_ends_at);
        $this->assertTrue($team->subscription_ends_at->isFuture());
    }
}
