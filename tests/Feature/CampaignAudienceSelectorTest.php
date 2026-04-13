<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactTag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignAudienceSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_audience_selector_renders_and_counts_contacts_by_tag(): void
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $tag = ContactTag::create(['team_id' => $team->id, 'name' => 'VIP']);

        $c1 = Contact::factory()->create(['team_id' => $team->id]);
        $c2 = Contact::factory()->create(['team_id' => $team->id]);
        $c1->tags()->attach($tag->id);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Campaigns\Wizard\AudienceSelector::class)
            ->assertSee('VIP')
            ->set('audienceType', 'tags')
            ->set('selectedTags', [$tag->id])
            ->assertSee('1');
    }

    public function test_audience_selector_all_counts_only_opted_in_contacts(): void
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);
        Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_out']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Campaigns\Wizard\AudienceSelector::class)
            ->set('audienceType', 'all')
            ->assertSee('1');
    }
}
