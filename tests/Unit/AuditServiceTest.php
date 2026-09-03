<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_handles_non_existent_team_id_gracefully(): void
    {
        $user = User::factory()->create([
            'current_team_id' => 999999, // Non-existent team ID
        ]);

        AuditService::log('auth.login', "User '{$user->name}' logged in.", $user);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'team_id' => null,
            'event_type' => 'auth.login',
        ]);
    }

    public function test_audit_log_catches_exceptions_without_crashing(): void
    {
        // Should not throw even with unexpected input types or DB exceptions
        AuditService::log('auth.login', null, null, null, []);
        $this->assertTrue(true);
    }
}
