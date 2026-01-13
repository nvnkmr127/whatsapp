<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_variables_returns_true_for_match()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, welcome to {{2}}']
            ]
        ]);

        $service = new TemplateService();
        $this->assertTrue($service->validateVariables($template, ['John', 'Our App']));
    }

    public function test_validate_variables_returns_false_for_mismatch()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'hello_world',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}']
            ]
        ]);

        $service = new TemplateService();
        $this->assertFalse($service->validateVariables($template, [])); // Missing var
        $this->assertFalse($service->validateVariables($template, ['John', 'Extra'])); // Too many vars
    }
}
