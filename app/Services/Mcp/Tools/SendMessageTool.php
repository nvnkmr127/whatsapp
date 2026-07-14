<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\OutboundPreflightService;
use App\Services\WhatsAppService;

class SendMessageTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_message',
            'description' => 'Send a plain text WhatsApp message to a phone number.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'      => ['type' => 'string', 'description' => 'Recipient phone number in E.164 format (e.g. +919876543210)'],
                    'message' => ['type' => 'string', 'description' => 'Text content of the message'],
                ],
                'required' => ['to', 'message'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        app(OutboundPreflightService::class)->authorizeOrFail($team, 'send_message', 'message_limit');

        $response = app(WhatsAppService::class)->setTeam($team)->sendText($input['to'], $input['message']);

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Message sent successfully. WhatsApp ID: {$wamId}"]];
    }
}
