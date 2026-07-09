<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class ListAutomationsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'list_automations',
            'description' => 'List automation flows available in this tenant account. Use IDs with start_automation.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'active_only' => ['type' => 'boolean', 'description' => 'Return only active automations', 'default' => true],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $query = \App\Models\Automation::where('team_id', $team->id);

        if ($input['active_only'] ?? true) {
            $query->where('is_active', true);
        }

        $automations = $query->get()->map(fn($a) => [
            'id'        => $a->id,
            'name'      => $a->name,
            'trigger'   => $a->trigger ?? null,
            'is_active' => $a->is_active,
        ]);

        if ($automations->isEmpty()) {
            return [['type' => 'text', 'text' => 'No automations found.']];
        }

        return [['type' => 'text', 'text' => json_encode($automations, JSON_PRETTY_PRINT)]];
    }
}
