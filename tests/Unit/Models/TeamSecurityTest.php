<?php

namespace Tests\Unit\Models;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_attributes_are_guarded_against_mass_assignment()
    {
        $team = Team::factory()->create();

        try {
            $team->update([
                'subscription_status' => 'active',
            ]);
        } catch (\Illuminate\Database\Eloquent\MassAssignmentException $e) {
            $this->assertTrue(true);
            return;
        }

        // Refresh from DB
        $team->refresh();

        // Assert sensitive fields were NOT updated
        // Note: Factory might set random values, so we check they didn't become the 'hacked' values.
        $this->assertNotEquals('active', $team->subscription_status, 'Subscription Status guarded');

    }
}
