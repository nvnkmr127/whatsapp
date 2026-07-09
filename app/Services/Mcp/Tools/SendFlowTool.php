<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class SendFlowTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_flow',
            'description' => 'Send a WhatsApp Flow message to a contact. The flow must be created and configured in your account.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'             => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'flow_id'        => ['type' => 'string', 'description' => 'Flow ID or local DB ID of the WhatsApp Flow'],
                    'headline'       => ['type' => 'string', 'description' => 'Header text of the flow message'],
                    'body'           => ['type' => 'string', 'description' => 'Body text describing the flow'],
                    'cta'            => ['type' => 'string', 'description' => 'Call-to-action button label (e.g. "Fill Form")'],
                    'mode'           => ['type' => 'string', 'enum' => ['draft', 'published'], 'default' => 'published'],
                    'initial_screen' => ['type' => 'string', 'description' => 'ID of the initial screen to show (optional)'],
                    'data'           => ['type' => 'object', 'description' => 'Initial data to pass into the flow', 'default' => []],
                ],
                'required' => ['to', 'flow_id', 'headline', 'body', 'cta'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $response = app(WhatsAppService::class)->setTeam($team)->sendFlow(
            $input['to'],
            $input['flow_id'],
            $input['headline'],
            $input['body'],
            $input['cta'],
            $input['mode'] ?? 'published',
            $input['initial_screen'] ?? null,
            $input['data'] ?? []
        );

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Flow message sent. WhatsApp ID: {$wamId}"]];
    }
}
