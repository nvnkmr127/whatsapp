<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\TemplateService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TemplateAuditVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify that placeholders in buttons are correctly counted.
     */
    public function test_count_placeholders_includes_buttons()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'button_promo',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Deal for you!'],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        ['type' => 'URL', 'text' => 'Claim', 'url' => 'https://site.com/{{1}}']
                    ]
                ]
            ]
        ]);

        $service = new TemplateService();
        $this->assertTrue($service->validateVariables($template, ['token123']));
    }

    /**
     * Verify that sendTemplate does NOT steal body variables for header placeholders.
     */
    public function test_send_template_does_not_steal_body_variables()
    {
        $team = Team::factory()->create([
            'whatsapp_setup_state' => \App\Enums\IntegrationState::READY
        ]);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'media_test',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'HEADER', 'format' => 'IMAGE'],
                ['type' => 'BODY', 'text' => 'Hello {{1}}']
            ]
        ]);

        Http::fake([
            '*' => Http::response(['success' => true, 'messages' => [['id' => 'wam_123']]], 200)
        ]);

        // Mock Billing and Policy to bypass checks
        $this->mock(\App\Services\BillingService::class, function ($mock) {
            $mock->shouldReceive('recordConversationUsage')->andReturn(true);
        });
        $this->mock(\App\Services\PolicyService::class, function ($mock) {
            $mock->shouldReceive('canSendTemplate')->andReturn(true);
            $mock->shouldReceive('canSendFreeMessage')->andReturn(true);
        });

        $ws = new WhatsAppService($team);

        // We pass header param explicitly (URL) and body param (John)
        $response = $ws->sendTemplate(
            to: '1234567890',
            templateName: 'media_test',
            language: 'en_US',
            bodyParams: ['John'],
            headerParams: ['https://example.com/img.png']
        );

        $this->assertTrue($response['success'] ?? false);

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $components = $payload['template']['components'];

            $header = collect($components)->firstWhere('type', 'header');
            $body = collect($components)->firstWhere('type', 'body');

            // Header should have the image link
            $hasHeaderImg = $header['parameters'][0]['image']['link'] === 'https://example.com/img.png';

            // Body should have 'John', NOT the image link (which would happen if it was stolen)
            $hasBodyJohn = $body['parameters'][0]['text'] === 'John';

            return $hasHeaderImg && $hasBodyJohn;
        });
    }
}
