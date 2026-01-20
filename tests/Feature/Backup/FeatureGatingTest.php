<?php

namespace Tests\Feature\Backup;

use App\Models\Plan;
use App\Models\Team;
use App\Models\TeamAddOn;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;

class FeatureGatingTest extends TestCase
{
    use RefreshDatabase;

    protected $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = new BackupService();

        // Setup default plans
        Plan::create([
            'name' => 'basic',
            'monthly_price' => 0,
            'message_limit' => 100,
            'agent_limit' => 1,
            'features' => ['backups' => true, 'cloud_backups' => false]
        ]);
        Plan::create([
            'name' => 'pro',
            'monthly_price' => 29,
            'message_limit' => 1000,
            'agent_limit' => 5,
            'features' => ['backups' => true, 'cloud_backups' => true]
        ]);
    }

    public function test_basic_plan_cannot_use_cloud_backups()
    {
        $team = Team::factory()->create(['subscription_plan' => 'basic']);

        // Manual check of the service check
        $this->assertFalse($team->hasFeature('cloud_backups'));
    }

    public function test_pro_plan_can_use_cloud_backups()
    {
        $team = Team::factory()->create(['subscription_plan' => 'pro']);

        $this->assertTrue($team->hasFeature('cloud_backups'));
    }

    public function test_addon_enables_restricted_feature()
    {
        $team = Team::factory()->create(['subscription_plan' => 'basic']);

        // Initially fails
        $this->assertFalse($team->hasFeature('cloud_backups'));

        // Add the add-on
        TeamAddOn::create([
            'team_id' => $team->id,
            'type' => 'cloud_backups',
            'expires_at' => now()->addDay()
        ]);

        $this->assertTrue($team->hasFeature('cloud_backups'));
    }

    public function test_expired_subscription_blocks_all_features()
    {
        $team = Team::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_status' => 'expired'
        ]);

        $this->assertFalse($team->hasFeature('backups'));
        $this->assertFalse($team->hasFeature('cloud_backups'));
    }

    public function test_backup_service_throws_on_restricted_access()
    {
        $team = Team::factory()->create(['subscription_plan' => 'basic', 'subscription_status' => 'active']);

        // Remove 'backups' from basic for this test
        $plan = Plan::where('name', 'basic')->first();
        $plan->update(['features' => ['backups' => false]]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The backup feature is not available for your team.");

        $this->backupService->backupTenant($team);
    }
}
