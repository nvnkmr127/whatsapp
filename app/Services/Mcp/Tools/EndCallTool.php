<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Models\WhatsAppCall;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class EndCallTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'end_call',
            'description' => 'End an active WhatsApp call by call ID.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'call_id' => ['type' => 'string', 'description' => 'WhatsApp call ID (from initiate_call or webhook)'],
                ],
                'required' => ['call_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        app(WhatsAppService::class)->setTeam($team)->endCall($input['call_id']);

        return [['type' => 'text', 'text' => "Call {$input['call_id']} ended."]];
    }
}
