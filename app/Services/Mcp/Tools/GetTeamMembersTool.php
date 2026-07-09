<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetTeamMembersTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_team_members',
            'description' => 'List all team members with their user IDs. Use IDs with assign_conversation.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [],
                'required'   => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $members = $team->allUsers()->map(fn($u) => [
            'id'   => $u->id,
            'name' => $u->name,
            'role' => $u->membership?->role ?? 'member',
        ]);

        return [['type' => 'text', 'text' => json_encode($members, JSON_PRETTY_PRINT)]];
    }
}
