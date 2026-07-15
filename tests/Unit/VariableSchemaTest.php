<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_variable_extraction()
    {
        $service = new TemplateService;
        $text = 'Hello {{1}}, your order {{2}} is ready.';
        $vars = $service->extractVariables($text);

        $this->assertCount(2, $vars);
        $this->assertContains('{{1}}', $vars);
        $this->assertContains('{{2}}', $vars);
    }


    public function test_variable_config_storage()
    {
        $team = Team::factory()->create();
        $template = WhatsappTemplate::create([
            'team_id' => $team->id,
            'name' => 'test_storage',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [],
            'variable_config' => [
                '{{1}}' => ['name' => 'test_var', 'type' => 'CURRENCY'],
            ],
        ]);

        $this->assertIsArray($template->variable_config);
        $this->assertEquals('CURRENCY', $template->variable_config['{{1}}']['type']);
    }
}
