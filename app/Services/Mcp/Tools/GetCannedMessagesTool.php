<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetCannedMessagesTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_canned_messages',
            'description' => 'Retrieve all canned (saved) quick-reply messages for this tenant. Use these shortcuts to quickly send pre-written responses.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Filter by shortcut or content (optional)'],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $query = \App\Models\CannedMessage::where('team_id', $team->id)->orderBy('shortcut');

        if (! empty($input['search'])) {
            $s = $input['search'];
            $query->where(function ($q) use ($s) {
                $q->where('shortcut', 'like', "%{$s}%")
                    ->orWhere('content', 'like', "%{$s}%");
            });
        }

        $messages = $query->limit(200)->get()->map(fn($m) => [
            'id'       => $m->id,
            'shortcut' => $m->shortcut,
            'content'  => $m->content,
        ]);

        if ($messages->isEmpty()) {
            return [['type' => 'text', 'text' => 'No canned messages found.']];
        }

        return [['type' => 'text', 'text' => json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
