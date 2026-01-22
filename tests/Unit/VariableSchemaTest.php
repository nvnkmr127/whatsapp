<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VariableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_variable_extraction()
    {
        $service = new TemplateService();
        $text = "Hello {{1}}, your order {{2}} is ready.";
        $vars = $service->extractVariables($text);

        $this->assertCount(2, $vars);
        $this->assertContains('{{1}}', $vars);
        $this->assertContains('{{2}}', $vars);
    }

    public function test_hydration_logic()
    {
        $template = new WhatsappTemplate();
        $template->name = 'order_update';

        // Mock Schema
        $template->variable_config = [
            '{{1}}' => ['name' => 'customer_name', 'fallback' => 'Customer'],
            '{{2}}' => ['name' => 'order_id'],
        ];

        // Mock Components to ensure extraction works
        $template->components = [
            ['type' => 'BODY', 'text' => 'Hi {{1}}, order {{2}} confirmed.']
        ];

        $service = new TemplateService();
        $data = [
            'customer_name' => 'John',
            'order_id' => 'ORD-123'
        ];

        $result = $service->hydrateTemplate($template, $data);

        // Expect positional array: ['John', 'ORD-123']
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertEquals('ORD-123', $result[1]);
    }

    public function test_hydration_fallback()
    {
        $template = new WhatsappTemplate();
        $template->components = [['type' => 'BODY', 'text' => 'Hi {{1}}']];
        $template->variable_config = [
            '{{1}}' => ['name' => 'customer_name', 'fallback' => 'Friend'],
        ];

        $service = new TemplateService();
        $data = []; // No name provided

        $result = $service->hydrateTemplate($template, $data);
        $this->assertEquals('Friend', $result[0]);
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
                '{{1}}' => ['name' => 'test_var', 'type' => 'CURRENCY']
            ]
        ]);

        $this->assertIsArray($template->variable_config);
        $this->assertEquals('CURRENCY', $template->variable_config['{{1}}']['type']);
    }
}
