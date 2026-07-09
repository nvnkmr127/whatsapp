<?php

namespace App\Services\Mcp\Tools;

use App\Models\Conversation;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class ListConversationsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'list_conversations',
            'description' => 'List recent conversations for the tenant. Returns contact name, phone, status, and last message time.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'status' => [
                        'type'        => 'string',
                        'enum'        => ['open', 'resolved', 'pending'],
                        'description' => 'Filter by conversation status',
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'description' => 'Maximum results (1–50)',
                        'default'     => 20,
                        'minimum'     => 1,
                        'maximum'     => 50,
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $limit = min((int) ($input['limit'] ?? 20), 50);

        $query = Conversation::where('team_id', $team->id)
            ->with('contact:id,name,phone_number')
            ->latest('updated_at')
            ->limit($limit);

        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }

        $rows = $query->get()->map(fn($c) => [
            'id'           => $c->id,
            'contact_name' => $c->contact?->name,
            'phone'        => $c->contact?->phone_number,
            'status'       => $c->status,
            'updated_at'   => $c->updated_at?->toDateTimeString(),
        ]);

        return [['type' => 'text', 'text' => json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
