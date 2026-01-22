<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use App\Validators\TemplateValidator;
use App\Services\WhatsAppService;
use App\Helpers\PhoneNumberHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_category_blocks_media()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'auth_with_img',
            'language' => 'en_US',
            'category' => 'AUTHENTICATION',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'HEADER', 'format' => 'IMAGE'],
                ['type' => 'BODY', 'text' => 'Code: {{1}}']
            ]
        ]);

        $validator = new TemplateValidator();
        $validator->validate($template);

        $this->assertEquals(0, $template->readiness_score);
        $this->assertCount(2, $template->validation_results);
        $codes = collect($template->validation_results)->pluck('code');
        $this->assertTrue($codes->contains('CAT_AUTH_MEDIA_DISALLOWED'));
        $this->assertTrue($codes->contains('MEDIA_UNBOUND'));
    }

    public function test_auth_category_blocks_invalid_buttons()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'auth_with_url',
            'language' => 'en_US',
            'category' => 'AUTHENTICATION',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Code: {{1}}'],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        ['type' => 'URL', 'text' => 'Visit', 'url' => 'https://site.com']
                    ]
                ]
            ]
        ]);

        $validator = new TemplateValidator();
        $validator->validate($template);

        $this->assertEquals(0, $template->readiness_score);
        $this->assertEquals('CAT_AUTH_BUTTON_INVALID', $template->validation_results[0]['code']);
    }

    public function test_marketing_blocked_without_consent()
    {
        $team = Team::factory()->create([
            'whatsapp_setup_state' => \App\Enums\IntegrationState::READY
        ]);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => PhoneNumberHelper::normalize('1234567890'),
            'opt_in_status' => 'none' // No consent
        ]);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'promo',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Sale!']]
        ]);

        // Mock PolicyService
        $this->mock(\App\Services\PolicyService::class, function ($mock) {
            $mock->shouldReceive('canSendTemplate')->andReturn(true);
        });

        $service = new WhatsAppService($team);
        $response = $service->sendTemplate('1234567890', 'promo');

        $this->assertFalse($response['success']);
        $errorMsg = is_array($response['error']) ? json_encode($response['error']) : (string) $response['error'];
        $this->assertStringContainsString('CAT_MARKETING_NO_OPT_IN', $errorMsg);
    }

    public function test_utility_blocked_for_opted_out()
    {
        $team = Team::factory()->create([
            'whatsapp_setup_state' => \App\Enums\IntegrationState::READY
        ]);

        $contact = Contact::create([
            'team_id' => $team->id,
            'phone_number' => PhoneNumberHelper::normalize('1234567890'),
            'opt_in_status' => 'opted_out'
        ]);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'order_update',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Your order is ready']]
        ]);

        $service = new WhatsAppService($team);
        $response = $service->sendTemplate('1234567890', 'order_update');

        $this->assertFalse($response['success']);
        $errorMsg = is_array($response['error']) ? json_encode($response['error']) : (string) $response['error'];
        $this->assertStringContainsString('CAT_UTILITY_BLOCKED', $errorMsg);
    }
}
