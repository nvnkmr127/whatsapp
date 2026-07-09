<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class SendInteractiveListTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_interactive_list',
            'description' => 'Send a WhatsApp list message with sections and rows (up to 10 rows total). Requires an open 24-hour window.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'          => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'text'        => ['type' => 'string', 'description' => 'Body text of the message'],
                    'button_text' => ['type' => 'string', 'description' => 'Label for the list button (e.g. "View Options")'],
                    'sections'    => [
                        'type'        => 'array',
                        'description' => 'Array of sections, each with title and rows array',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'rows'  => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'id'          => ['type' => 'string'],
                                            'title'       => ['type' => 'string'],
                                            'description' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'required' => ['to', 'text', 'button_text', 'sections'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $response = app(WhatsAppService::class)->setTeam($team)
            ->sendInteractiveList($input['to'], $input['text'], $input['button_text'], $input['sections']);

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Interactive list sent. WhatsApp ID: {$wamId}"]];
    }
}
