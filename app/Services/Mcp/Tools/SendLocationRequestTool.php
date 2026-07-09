<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class SendLocationRequestTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_location_request',
            'description' => 'Ask a contact to share their live location via WhatsApp.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'   => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'text' => ['type' => 'string', 'description' => 'Message body text asking for location'],
                ],
                'required' => ['to', 'text'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $response = app(WhatsAppService::class)->setTeam($team)
            ->sendLocationRequest($input['to'], $input['text']);

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Location request sent. WhatsApp ID: {$wamId}"]];
    }
}
