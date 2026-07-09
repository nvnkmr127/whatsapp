<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class InitiateCallTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'initiate_call',
            'description' => 'Initiate an outbound WhatsApp voice call to a contact. Requires calling to be enabled on the tenant account and within business hours.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to' => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                ],
                'required' => ['to'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $response = app(WhatsAppService::class)->setTeam($team)->initiateCall($input['to']);

        $callId = $response['data']['id'] ?? $response['data']['calls'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Call initiated. Call ID: {$callId}"]];
    }
}
