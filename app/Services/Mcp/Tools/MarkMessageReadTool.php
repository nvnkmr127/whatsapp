<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class MarkMessageReadTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'mark_message_read',
            'description' => 'Mark an inbound WhatsApp message as read (sends a read receipt). Use the WhatsApp message_id (wam_...) returned from incoming webhook payloads.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'message_id' => ['type' => 'string', 'description' => 'WhatsApp message ID (e.g. wam_XXXX)'],
                ],
                'required' => ['message_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        app(WhatsAppService::class)->setTeam($team)->markRead($input['message_id']);

        return [['type' => 'text', 'text' => "Message {$input['message_id']} marked as read."]];
    }
}
