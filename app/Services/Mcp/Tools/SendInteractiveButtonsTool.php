<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class SendInteractiveButtonsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_interactive_buttons',
            'description' => 'Send a WhatsApp message with up to 3 quick-reply buttons. Requires an open 24-hour conversation window.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'      => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'text'    => ['type' => 'string', 'description' => 'Body text of the message'],
                    'buttons' => [
                        'type'        => 'object',
                        'description' => 'Key-value map of button_id → button_title (max 3, title max 20 chars)',
                        'example'     => ['yes' => 'Yes', 'no' => 'No, thanks'],
                    ],
                ],
                'required' => ['to', 'text', 'buttons'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        if (count($input['buttons']) > 3) {
            throw new \Exception('WhatsApp allows a maximum of 3 interactive buttons.');
        }

        $response = app(WhatsAppService::class)->setTeam($team)
            ->sendInteractiveButtons($input['to'], $input['text'], $input['buttons']);

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Interactive buttons sent. WhatsApp ID: {$wamId}"]];
    }
}
