<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['user_id' => $this->user->id]);
        $this->user->teams()->attach($this->team, ['role' => 'admin']);
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();

        Sanctum::actingAs($this->user);
    }

    public function test_can_list_campaigns()
    {
        $template = WhatsappTemplate::factory()->create(['team_id' => $this->team->id]);

        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'name' => 'Past Mobile Broadcast Campaign',
            'campaign_name' => 'Past Mobile Broadcast Campaign',
            'status' => 'completed',
            'template_id' => $template->id,
            'whatsapp_template_id' => $template->whatsapp_template_id,
            'total_contacts' => 5,
        ]);

        $response = $this->getJson('/api/v1/mobile/campaigns', [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $campaign->id);
        $response->assertJsonPath('data.0.name', 'Past Mobile Broadcast Campaign');
    }

    public function test_can_store_campaign()
    {
        $template = WhatsappTemplate::factory()->create(['team_id' => $this->team->id]);
        $tag = ContactTag::create(['team_id' => $this->team->id, 'name' => 'VIP Customers']);

        $payload = [
            'name' => 'New Broadcast Test Campaign',
            'template_id' => $template->id,
            'tag_id' => $tag->id,
        ];

        $response = $this->postJson('/api/v1/mobile/campaigns', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('campaign.name', 'New Broadcast Test Campaign');

        $this->assertDatabaseHas('campaigns', [
            'team_id' => $this->team->id,
            'name' => 'New Broadcast Test Campaign',
            'template_id' => $template->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_can_store_scheduled_campaign()
    {
        $template = WhatsappTemplate::factory()->create(['team_id' => $this->team->id]);
        $tag = ContactTag::create(['team_id' => $this->team->id, 'name' => 'VIP Customers']);
        $futureDate = now()->addHours(2)->toIso8601String();

        $payload = [
            'name' => 'New Scheduled Campaign',
            'template_id' => $template->id,
            'tag_id' => $tag->id,
            'scheduled_at' => $futureDate,
        ];

        $response = $this->postJson('/api/v1/mobile/campaigns', $payload, [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('campaigns', [
            'team_id' => $this->team->id,
            'name' => 'New Scheduled Campaign',
            'template_id' => $template->id,
            'status' => 'scheduled',
        ]);

        $campaign = Campaign::where('name', 'New Scheduled Campaign')->first();
        $this->assertNotNull($campaign->scheduled_at);
        $this->assertTrue(\Carbon\Carbon::parse($campaign->scheduled_at)->isFuture());
    }

    public function test_can_get_campaign_details()
    {
        $template = WhatsappTemplate::factory()->create(['team_id' => $this->team->id]);

        $campaign = Campaign::create([
            'team_id' => $this->team->id,
            'name' => 'Details Test Campaign',
            'campaign_name' => 'Details Test Campaign',
            'status' => 'completed',
            'template_id' => $template->id,
            'whatsapp_template_id' => $template->whatsapp_template_id,
            'total_contacts' => 10,
            'sent_count' => 8,
            'del_count' => 6,
            'read_count' => 4,
        ]);

        $response = $this->getJson("/api/v1/mobile/campaigns/{$campaign->id}", [
            'X-Tenant-ID' => $this->team->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('id', $campaign->id);
        $response->assertJsonPath('name', 'Details Test Campaign');
        $response->assertJsonPath('delivery_percentage', 80);
        $response->assertJsonPath('read_percentage', 50);
        $response->assertJsonStructure([
            'id', 'name', 'status', 'total_contacts', 'sent_count', 'del_count', 'read_count',
            'delivery_percentage', 'read_percentage', 'template', 'messages'
        ]);
    }
}
