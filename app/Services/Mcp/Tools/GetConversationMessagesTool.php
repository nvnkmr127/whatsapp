<?php

namespace App\Services\Mcp\Tools;

use App\Models\Conversation;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetConversationMessagesTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_conversation_messages',
            'description' => 'Retrieve the message history of a specific conversation.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID from list_conversations'],
                    'limit'           => ['type' => 'integer', 'description' => 'Max messages to return (1–100)', 'default' => 30, 'minimum' => 1, 'maximum' => 100],
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

        $limit = min((int) ($input['limit'] ?? 30), 100);

        $messages = $conversation->messages()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'direction'  => $m->direction,
                'type'       => $m->type,
                'content'    => $m->content,
                'status'     => $m->status,
                'created_at' => $m->created_at?->toDateTimeString(),
            ])
            ->values();

        return [['type' => 'text', 'text' => json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
