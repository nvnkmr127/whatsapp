<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_analytics_dashboard()
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $user->currentTeam->users()->attach($user, ['role' => 'admin']);

        // Populate data
        $contact = \App\Models\Contact::factory()->create(['team_id' => $user->currentTeam->id]);
        \App\Models\Message::create([
            'team_id' => $user->currentTeam->id,
            'contact_id' => $contact->id,
            'whatsapp_message_id' => '123',
            'direction' => 'outbound',
            'status' => 'sent',
            'content' => 'test',
        ]);

        $this->actingAs($user)
            ->get(route('analytics'))
            ->assertStatus(200)
            ->assertSee('Analytics Dashboard');
    }

    public function test_can_view_campaign_show()
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $user->currentTeam->users()->attach($user, ['role' => 'admin']);

        $campaign = \App\Models\Campaign::create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Test Campaign',
            'campaign_name' => 'Test Campaign',
            'status' => 'scheduled'
        ]);

        $this->actingAs($user)
            ->get(route('campaigns.show', $campaign->id))
            ->assertStatus(200)
            ->assertSee('Test Campaign');
    }
}
