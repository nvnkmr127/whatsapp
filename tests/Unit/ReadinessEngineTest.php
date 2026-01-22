<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Validators\TemplateValidator;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ReadinessEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_status_affects_readiness_score()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'pending_test',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'PENDING',
            'components' => [['type' => 'BODY', 'text' => 'Hello']]
        ]);

        $validator = new TemplateValidator();
        $validator->validate($template);

        $this->assertEquals(50, $template->readiness_score); // 100 - 50 for not APPROVED
        $this->assertCount(1, $template->validation_results);
        $this->assertEquals('STATUS_INELIGIBLE', $template->validation_results[0]['code']);
    }

    public function test_sequential_variable_validation()
    {
        $team = Team::factory()->create();

        // Correct sequence
        $tpl1 = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'ok',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => '{{1}} and {{2}}']]
        ]);

        // Jumbled sequence
        $tpl2 = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'bad',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => '{{2}} and {{1}}']]
        ]);

        $validator = new TemplateValidator();

        $validator->validate($tpl1);
        $this->assertEquals(100, $tpl1->readiness_score);

        $validator->validate($tpl2);
        $this->assertEquals(60, $tpl2->readiness_score); // 100 - 40 for VARIABLE_SKEW
    }

    public function test_whatsapp_service_blocks_low_readiness_templates()
    {
        $team = Team::factory()->create([
            'whatsapp_setup_state' => \App\Enums\IntegrationState::READY
        ]);

        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'broken_tpl',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'REJECTED', // Fatal for readiness
            'components' => [['type' => 'BODY', 'text' => 'Hello']]
        ]);

        $service = new WhatsAppService($team);
        $response = $service->sendTemplate('1234567890', 'broken_tpl');

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Template is not ready', $response['error']);
    }

    public function test_dynamic_button_validation()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'bad_btn',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hi'],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        ['type' => 'URL', 'text' => 'View', 'url' => 'https://site.com/{{2}}'] // Must be {{1}}
                    ]
                ]
            ]
        ]);

        $validator = new TemplateValidator();
        $validator->validate($template);

        $this->assertEquals(80, $template->readiness_score); // 100 - 20
        $this->assertEquals('BUTTON_VARIABLE_INVALID', $template->validation_results[0]['code']);
    }
}
