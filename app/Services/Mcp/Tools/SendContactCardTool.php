<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class SendContactCardTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_contact_card',
            'description' => 'Send a contact (vCard) to a WhatsApp recipient.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'      => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'contact' => [
                        'type'        => 'object',
                        'description' => 'WhatsApp contact object per Meta API spec',
                        'properties'  => [
                            'name'   => [
                                'type'       => 'object',
                                'properties' => [
                                    'formatted_name' => ['type' => 'string'],
                                    'first_name'     => ['type' => 'string'],
                                    'last_name'      => ['type' => 'string'],
                                ],
                                'required' => ['formatted_name'],
                            ],
                            'phones' => [
                                'type'  => 'array',
                                'items' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'phone' => ['type' => 'string'],
                                        'type'  => ['type' => 'string', 'enum' => ['CELL', 'MAIN', 'IPHONE', 'HOME', 'WORK']],
                                    ],
                                ],
                            ],
                            'emails' => [
                                'type'  => 'array',
                                'items' => ['type' => 'object', 'properties' => ['email' => ['type' => 'string'], 'type' => ['type' => 'string']]],
                            ],
                        ],
                        'required' => ['name'],
                    ],
                ],
                'required' => ['to', 'contact'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        app(\App\Services\OutboundPreflightService::class)->authorizeOrFail($team, 'send_message', 'message_limit');

        $response = app(WhatsAppService::class)->setTeam($team)
            ->sendContact($input['to'], $input['contact']);

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Contact card sent. WhatsApp ID: {$wamId}"]];
    }
}
