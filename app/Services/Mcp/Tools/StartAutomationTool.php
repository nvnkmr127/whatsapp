<?php

namespace App\Services\Mcp\Tools;

use App\Models\Automation;
use App\Models\Team;
use App\Services\AutomationService;
use App\Services\Mcp\McpTool;

class StartAutomationTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'start_automation',
            'description' => 'Trigger an automation flow for a contact. The automation must already exist in the tenant\'s account.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'automation_id' => ['type' => 'integer', 'description' => 'ID of the automation to trigger'],
                    'phone'         => ['type' => 'string', 'description' => 'Contact phone in E.164 format'],
                    'variables'     => [
                        'type'                 => 'object',
                        'description'          => 'Key-value pairs to inject as automation variables',
                        'additionalProperties' => ['type' => 'string'],
                        'default'              => [],
                    ],
                ],
                'required' => ['automation_id', 'phone'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $automation = Automation::where('id', $input['automation_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $phone = \App\Helpers\PhoneNumberHelper::normalize($input['phone']);

        // Find or create contact (same pattern as ProcessMappedWebhookJob)
        $contact = \App\Models\Contact::firstOrCreate(
            ['team_id' => $team->id, 'phone_number' => $phone],
            ['name' => 'MCP Contact']
        );

        app(AutomationService::class)->start($automation, $contact, $input['variables'] ?? []);

        return [['type' => 'text', 'text' => "Automation '{$automation->name}' started for contact {$phone}."]];
    }
}
