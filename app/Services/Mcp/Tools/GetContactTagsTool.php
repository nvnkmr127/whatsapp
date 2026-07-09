<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class GetContactTagsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_contact_tags',
            'description' => 'Retrieve all available contact tags for this tenant.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [],
                'required'   => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $tags = \App\Models\ContactTag::where('team_id', $team->id)
            ->get()
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name]);

        if ($tags->isEmpty()) {
            return [['type' => 'text', 'text' => 'No contact tags found.']];
        }

        return [['type' => 'text', 'text' => json_encode($tags, JSON_PRETTY_PRINT)]];
    }
}
