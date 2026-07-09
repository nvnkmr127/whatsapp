<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class SearchContactsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'search_contacts',
            'description' => 'Search contacts by name, phone, or email. Returns up to 25 matches.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Search term (name, phone, or email)'],
                    'limit' => ['type' => 'integer', 'default' => 25, 'minimum' => 1, 'maximum' => 50],
                ],
                'required' => ['query'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $q     = $input['query'];
        $limit = min((int) ($input['limit'] ?? 25), 50);

        $contacts = \App\Models\Contact::where('team_id', $team->id)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'phone_number' => $c->phone_number,
                'email'        => $c->email,
            ]);

        if ($contacts->isEmpty()) {
            return [['type' => 'text', 'text' => "No contacts matching \"{$q}\"."]];
        }

        return [['type' => 'text', 'text' => json_encode($contacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
