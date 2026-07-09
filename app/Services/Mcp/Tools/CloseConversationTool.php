<?php

namespace App\Services\Mcp\Tools;

use App\Models\Conversation;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class CloseConversationTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'close_conversation',
            'description' => 'Mark a conversation as resolved/closed.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID'],
                ],
                'required' => ['conversation_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $conversation = Conversation::where('id', $input['conversation_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $conversation->update(['status' => 'closed', 'closed_at' => now()]);

        return [['type' => 'text', 'text' => "Conversation #{$conversation->id} closed/resolved."]];
    }
}
