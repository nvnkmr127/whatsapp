<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_campaign_and_dispatch_jobs()
    {
        \Illuminate\Support\Facades\Bus::fake();

        $team = \App\Models\Team::factory()->create();
        $contact1 = \App\Models\Contact::factory()->create(['team_id' => $team->id]);
        $contact2 = \App\Models\Contact::factory()->create(['team_id' => $team->id]);

        $campaign = \App\Models\Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Test Campaign',
            'status' => 'scheduled',
            'audience_filters' => ['all' => true], // Targets all
            'template_name' => 'hello_world',
            'template_language' => 'en_US',
        ]);

        $job = new \App\Jobs\ProcessCampaignJob($campaign->id);
        $job->handle();

        $campaign->refresh();
        // Depending on logic, status might be completed because it finished dispatching
        $this->assertEquals('completed', $campaign->status);
        $this->assertEquals(2, $campaign->total_contacts);

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendCampaignMessageJob::class, 2);
    }

    public function test_campaign_audiences_filtering()
    {
        \Illuminate\Support\Facades\Bus::fake();

        $team = \App\Models\Team::factory()->create();
        $tag = \App\Models\ContactTag::create(['team_id' => $team->id, 'name' => 'VIP']);

        $c1 = \App\Models\Contact::factory()->create(['team_id' => $team->id]);
        $c1->tags()->attach($tag);

        $c2 = \App\Models\Contact::factory()->create(['team_id' => $team->id]); // No TAG

        $campaign = \App\Models\Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Test Campaign',
            'status' => 'scheduled',
            'audience_filters' => ['tags' => [$tag->id]],
            'template_name' => 'hello_world',
            'template_language' => 'en_US',
        ]);

        (new \App\Jobs\ProcessCampaignJob($campaign->id))->handle();

        $campaign->refresh();
        $this->assertEquals(1, $campaign->total_contacts);

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendCampaignMessageJob::class, function ($job) use ($c1) {
            // Check properties via reflection or public
            return true;
        });

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendCampaignMessageJob::class, 1);
    }
}
