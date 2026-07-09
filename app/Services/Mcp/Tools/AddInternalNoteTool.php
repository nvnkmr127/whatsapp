<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class AddInternalNoteTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'add_internal_note',
            'description' => 'Add a private internal note to a conversation (not visible to the contact).',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID'],
                    'content'         => ['type' => 'string', 'description' => 'Note text content'],
                ],
                'required' => ['conversation_id', 'content'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $conversation = \App\Models\Conversation::where('id', $input['conversation_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $note = \App\Models\InternalNote::create([
            'team_id'         => $team->id,
            'conversation_id' => $conversation->id,
            'content'         => $input['content'],
            'user_id'         => null, // MCP = system-authored, no user context
        ]);

        return [['type' => 'text', 'text' => "Internal note added (ID: {$note->id}) to conversation #{$conversation->id}."]];
    }
}
