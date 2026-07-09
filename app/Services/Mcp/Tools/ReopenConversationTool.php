<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class ReopenConversationTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'reopen_conversation',
            'description' => 'Reopen a previously closed conversation, setting its status back to open.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID to reopen'],
                ],
                'required' => ['conversation_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $conversation = \App\Models\Conversation::where('id', $input['conversation_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $conversation->update(['status' => 'open', 'closed_at' => null]);

        return [['type' => 'text', 'text' => "Conversation #{$conversation->id} reopened."]];
    }
}
