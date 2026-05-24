<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_process_campaign_and_dispatch_jobs()
    {
        \Illuminate\Support\Facades\Bus::fake();

        $team = \App\Models\Team::factory()->create([
            'whatsapp_access_token' => 'test-token',
            'whatsapp_phone_number_id' => '123',
            'whatsapp_setup_state' => \App\Enums\IntegrationState::ACTIVE,
            'whatsapp_connected' => true,
        ]);
        $contact1 = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);
        $contact2 = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);

        // Ensure entitlement cache is clean
        app(\App\Services\EntitlementService::class)->flush($team);

        // Let's just create the actual plan instead of relying on overrides which might fail
        \App\Models\Plan::firstOrCreate(
            ['name' => 'test_pro'],
            ['monthly_price' => 50, 'features' => ['campaigns' => true], 'message_limit' => 10000, 'max_call_minutes_per_month' => 1000]
        );

        $team->update([
            'subscription_plan' => 'test_pro',
            'subscription_status' => 'active',
        ]);

        // Give explicit override for message_limit in DB
        \App\Models\BillingOverride::updateOrCreate(
            ['team_id' => $team->id, 'key' => 'message_limit'],
            ['value' => 10000, 'type' => 'limit_increase', 'reason' => 'Testing']
        );

        // Let's ensure the wallet has balance
        \App\Models\TeamWallet::where('team_id', $team->id)->delete();
        \App\Models\TeamWallet::create([
            'team_id' => $team->id,
            'balance' => 100.00,
        ]);

        // Ensure entitlement cache is clean
        app(\App\Services\EntitlementService::class)->flush($team);

        $campaign = \App\Models\Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Test Campaign',
            'status' => 'scheduled',
            'total_contacts' => 2,
            'audience_filters' => ['all' => true], // Targets all
            'template_name' => 'hello_world',
            'template_language' => 'en_US',
        ]);

        \Illuminate\Support\Facades\DB::table('campaign_details')->insert([
            ['campaign_id' => $campaign->id, 'contact_id' => $contact1->id, 'variant' => 'A', 'phone' => $contact1->phone_number],
            ['campaign_id' => $campaign->id, 'contact_id' => $contact2->id, 'variant' => 'A', 'phone' => $contact2->phone_number],
        ]);

        $job = new \App\Jobs\ProcessCampaignJob($campaign->id);
        app()->call([$job, 'handle']);

        $campaign->refresh();
        // Depending on logic, status might be completed because it finished dispatching
        $this->assertEquals('processing', $campaign->status);
        $this->assertEquals(2, $campaign->total_contacts);

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\ProduceBroadcastEventsJob::class);
    }

    public function test_campaign_audiences_filtering()
    {
        \Illuminate\Support\Facades\Bus::fake();

        $team = \App\Models\Team::factory()->create([
            'whatsapp_access_token' => 'test-token',
            'whatsapp_phone_number_id' => '123',
            'whatsapp_setup_state' => \App\Enums\IntegrationState::ACTIVE,
            'whatsapp_connected' => true,
        ]);
        $tag = \App\Models\ContactTag::create(['team_id' => $team->id, 'name' => 'VIP']);

        $c1 = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);
        $c2 = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'opt_in_status' => 'opted_in']);

        // Allow sending messages by giving high limit
        app(\App\Services\EntitlementService::class)->flush($team);
        \App\Models\TeamWallet::where('team_id', $team->id)->delete();
        \App\Models\TeamWallet::create([
            'team_id' => $team->id,
            'balance' => 100.00,
        ]);

        $team->update([
            'subscription_plan' => 'pro',
            'subscription_status' => 'active',
        ]);

        \App\Models\BillingOverride::create([
            'team_id' => $team->id,
            'type' => 'limit_increase',
            'key' => 'message_limit',
            'value' => '1000',
            'reason' => 'Testing',
        ]);

        $campaign = \App\Models\Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Test Campaign',
            'status' => 'scheduled',
            'total_contacts' => 2,
            'audience_filters' => ['all' => true], // Targets all
            'template_name' => 'hello_world',
            'template_language' => 'en_US',
        ]);

        \Illuminate\Support\Facades\DB::table('campaign_details')->insert([
            ['campaign_id' => $campaign->id, 'contact_id' => $c1->id, 'variant' => 'A', 'phone' => $c1->phone_number],
            ['campaign_id' => $campaign->id, 'contact_id' => $c2->id, 'variant' => 'A', 'phone' => $c2->phone_number],
        ]);

        $job = new \App\Jobs\ProcessCampaignJob($campaign->id);
        app()->call([$job, 'handle']);

        $campaign->refresh();
        $this->assertEquals(2, $campaign->total_contacts);

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\ProduceBroadcastEventsJob::class);
    }

    public function test_message_status_updates_campaign_stats_and_details_with_string_status()
    {
        $team = \App\Models\Team::factory()->create();
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+1234567890']);

        $campaign = \App\Models\Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Metrics Test Campaign',
            'status' => 'processing',
            'total_contacts' => 1,
            'audience_filters' => ['all' => true],
            'template_name' => 'hello_world',
            'template_language' => 'en_US',
        ]);

        $detail = \App\Models\CampaignDetail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'variant' => 'A',
            'phone' => '+1234567890',
            'status' => 'pending',
        ]);

        $message = \App\Models\Message::create([
            'team_id' => $team->id,
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'whatsapp_message_id' => 'wam_test_123',
            'status' => 'sent',
            'direction' => 'outbound',
            'to' => '+1234567890',
        ]);

        // Dispatch UpdateMessageStatusJob to 'delivered'
        $job = new \App\Jobs\UpdateMessageStatusJob([
            'provider_message_id' => 'wam_test_123',
            'status' => 'delivered',
            'timestamp' => time(),
        ]);
        $job->handle();

        $campaign->refresh();
        $detail->refresh();
        $message->refresh();

        $this->assertEquals('delivered', $message->status);
        $this->assertEquals('delivered', $detail->status); // Verify stored as string 'delivered' (not rank integer)
        $this->assertEquals(1, $campaign->del_count); // Verify incremented correctly
    }

    public function test_replay_all_failed_messages_on_live_dashboard()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = \App\Models\User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;
        $contact = \App\Models\Contact::factory()->create(['team_id' => $team->id, 'phone_number' => '+1234567890']);

        $campaign = \App\Models\Campaign::create([
            'team_id' => $team->id,
            'campaign_name' => 'Failed Replay Test Campaign',
            'status' => 'active',
            'total_contacts' => 1,
            'audience_filters' => ['all' => true],
            'template_name' => 'hello_world',
            'template_language' => 'en_US',
        ]);

        $detail = \App\Models\CampaignDetail::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'variant' => 'A',
            'phone' => '+1234567890',
            'status' => 'failed',
        ]);

        $message = \App\Models\Message::create([
            'team_id' => $team->id,
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'whatsapp_message_id' => 'wam_failed_123',
            'status' => 'failed',
            'error_message' => 'Some API Error',
            'direction' => 'outbound',
            'to' => '+1234567890',
        ]);

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Campaigns\Dashboard::class, ['campaign' => $campaign])
            ->call('replayAllFailed')
            ->assertHasNoErrors();

        $message->refresh();
        $detail->refresh();

        $this->assertEquals('queued', $message->status);
        $this->assertEquals('pending', $detail->status);
        $this->assertNull($message->error_message);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendCampaignMessageJob::class, function ($job) use ($campaign, $contact) {
            $ref = new \ReflectionClass($job);
            $cId = $ref->getProperty('campaignId')->getValue($job);
            $conId = $ref->getProperty('contactId')->getValue($job);

            return $cId === $campaign->id && $conId === $contact->id;
        });
    }
}
