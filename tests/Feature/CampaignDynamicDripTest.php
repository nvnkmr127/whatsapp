<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignDynamicDripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure standard plan exists
        \App\Models\Plan::firstOrCreate(
            ['name' => 'pro'],
            ['monthly_price' => 50, 'features' => ['campaigns' => true], 'message_limit' => 10000, 'max_call_minutes_per_month' => 1000]
        );
    }

    public function test_dynamic_drip_campaign_triggers_on_tag_addition()
    {
        Queue::fake();

        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $tag = ContactTag::create(['team_id' => $team->id, 'name' => 'VIP']);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}'],
            ],
        ]);

        $campaign = Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Dynamic Trigger Test',
            'campaign_type' => 'drip',
            'drip_trigger_type' => 'tag_added',
            'drip_send_to_existing' => false,
            'status' => 'active',
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_language' => $template->language,
            'audience_filters' => [
                'type' => 'tags',
                'tags' => [$tag->id],
            ],
        ]);

        // Tag the contact
        $contact->tags()->attach($tag->id);

        // Verify ProcessContactTagTriggerJob was pushed to queue
        Queue::assertPushed(\App\Jobs\ProcessContactTagTriggerJob::class, function ($job) use ($contact, $tag) {
            return $job->contactId === $contact->id && $job->tagId === $tag->id;
        });

        // Run the trigger job manually to verify it processes the campaign correctly
        $triggerJob = new \App\Jobs\ProcessContactTagTriggerJob($contact->id, $tag->id);
        $triggerJob->handle();

        // Assert that a CampaignDetail is created and ProcessDripStepJob is dispatched
        $this->assertTrue(CampaignDetail::where('campaign_id', $campaign->id)->where('contact_id', $contact->id)->exists());
        $campaign->refresh();
        $this->assertEquals(1, $campaign->total_contacts);

        Queue::assertPushed(\App\Jobs\ProcessDripStepJob::class, function ($job) use ($campaign, $contact) {
            // Check private/protected properties using reflection or just direct property access if public
            $ref = new \ReflectionClass($job);
            $cId = $ref->getProperty('campaignId')->getValue($job);
            $conId = $ref->getProperty('contactId')->getValue($job);
            $step = $ref->getProperty('stepIndex')->getValue($job);

            return $cId === $campaign->id && $conId === $contact->id && $step === 0;
        });
    }

    public function test_dynamic_drip_campaign_deduplicates()
    {
        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $tag = ContactTag::create(['team_id' => $team->id, 'name' => 'VIP']);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}'],
            ],
        ]);

        $campaign = Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Deduplication Test',
            'campaign_type' => 'drip',
            'drip_trigger_type' => 'tag_added',
            'drip_send_to_existing' => false,
            'status' => 'active',
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_language' => $template->language,
            'audience_filters' => [
                'type' => 'tags',
                'tags' => [$tag->id],
            ],
        ]);

        // Create detail record manually to simulate already sent contact
        CampaignDetail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone' => $contact->phone_number,
            'status' => 'sent',
            'variant' => 'A',
        ]);

        $campaign->update(['total_contacts' => 1]);

        Queue::fake();

        // Fire tagging trigger
        $triggerJob = new \App\Jobs\ProcessContactTagTriggerJob($contact->id, $tag->id);
        $triggerJob->handle();

        // Verify that no extra CampaignDetail or Drip step jobs are queued (since it is already sent)
        $this->assertEquals(1, CampaignDetail::where('campaign_id', $campaign->id)->count());
        Queue::assertNotPushed(\App\Jobs\ProcessDripStepJob::class);
    }

    public function test_dynamic_drip_campaign_send_to_existing_launch()
    {
        Queue::fake();

        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $tag = ContactTag::create(['team_id' => $team->id, 'name' => 'VIP']);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);
        $contact->tags()->attach($tag->id);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}'],
            ],
        ]);

        $campaign = Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Send to Existing Drip',
            'campaign_type' => 'drip',
            'drip_trigger_type' => 'tag_added',
            'drip_send_to_existing' => true,
            'status' => 'preparing',
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_language' => $template->language,
            'audience_filters' => [
                'type' => 'tags',
                'tags' => [$tag->id],
            ],
        ]);

        // Run PrepareCampaignJob
        $prepJob = new \App\Jobs\PrepareCampaignJob($campaign->id, [
            'selection_type' => 'tags',
            'tags' => [$tag->id],
        ]);
        $prepJob->handle();

        $campaign->refresh();

        // Status should transition to 'active' rather than 'processing'
        $this->assertEquals('active', $campaign->status);
        $this->assertEquals(1, $campaign->total_contacts);

        // ProcessDripStepJob should be dispatched for existing contact
        Queue::assertPushed(\App\Jobs\ProcessDripStepJob::class);
    }
}
