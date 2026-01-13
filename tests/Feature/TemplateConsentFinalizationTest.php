<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Services\ConsentService;
use App\Services\PolicyService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TemplateConsentFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_fallback_logic()
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $this->actingAs($user);

        // Create 'en_US' template only
        WhatsAppTemplate::create([
            'team_id' => $team->id,
            'whatsapp_template_id' => '123',
            'name' => 'hello_world',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'category' => 'MARKETING',
            'components' => []
        ]);

        $service = new WhatsAppService();
        $service->setTeam($team);

        // Mock Http to prevent actual call (and assertion)
        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)
        ]);

        // Request 'es_ES' -> Should fallback to 'en_US'
        $result = $service->sendTemplate('1234567890', 'hello_world', 'es_ES');

        $this->assertTrue($result['success']);

        // Verify Payload used 'en_US'
        Http::assertSent(function ($request) {
            return $request['template']['language']['code'] === 'en_US';
        });
    }

    public function test_stop_keyword_enforcement_via_policy()
    {
        $contact = Contact::factory()->create(['opt_in_status' => 'opted_out']);
        $policy = new PolicyService();

        // Should BLOCK even if free window is open (which it isn't here, but status overrides)
        $this->assertFalse($policy->canSendFreeMessage($contact));
        $this->assertFalse($policy->canSendTemplate($contact, 'MARKETING'));
        $this->assertFalse($policy->canSendTemplate($contact, 'UTILITY')); // Global block
    }

    public function test_marketing_template_requires_explicit_opt_in()
    {
        $contact = Contact::factory()->create(['opt_in_status' => 'unknown']);
        $policy = new PolicyService();

        $this->assertFalse($policy->canSendTemplate($contact, 'MARKETING'));

        $contact->update(['opt_in_status' => 'opted_in']);
        $this->assertTrue($policy->canSendTemplate($contact, 'MARKETING'));
    }

    public function test_immutable_consent_logs()
    {
        $contact = Contact::factory()->create();
        $service = new ConsentService();

        $service->optIn($contact, 'web_form');
        $this->assertDatabaseHas('consent_logs', [
            'contact_id' => $contact->id,
            'action' => 'opt_in',
            'source' => 'web_form'
        ]);

        $service->optOut($contact);
        $this->assertDatabaseHas('consent_logs', [
            'contact_id' => $contact->id,
            'action' => 'opt_out'
        ]);

        // Logs count should be 2
        $this->assertEquals(2, \App\Models\ConsentLog::count());
    }
}
