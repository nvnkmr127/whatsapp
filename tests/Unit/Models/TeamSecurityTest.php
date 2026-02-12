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

        // Attempt to update sensitive fields via mass assignment
        $team->update([
            'subscription_status' => 'active',
            'whatsapp_access_token' => 'hacked_token',
        ]);

        // Refresh from DB
        $team->refresh();

        // Assert sensitive fields were NOT updated
        // Note: Factory might set random values, so we check they didn't become the 'hacked' values.
        $this->assertNotEquals('active', $team->subscription_status, 'Subscription Status guarded');
        $this->assertNotEquals('hacked_token', $team->whatsapp_access_token, 'Access Token guarded');

        // Verify explicit assignment still works
        $team->whatsapp_access_token = 'valid_token';
        $team->save();
        $this->assertEquals('valid_token', $team->fresh()->whatsapp_access_token);
    }
}
