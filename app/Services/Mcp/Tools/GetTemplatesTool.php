<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\Mcp\McpTool;

class GetTemplatesTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_templates',
            'description' => 'List approved WhatsApp message templates for this tenant. Use this before calling send_template to discover template names and parameter counts.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'search' => ['type' => 'string', 'description' => 'Optional name filter (partial match)'],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $query = WhatsappTemplate::where('team_id', $team->id)
            ->where('status', 'APPROVED');

        if (! empty($input['search'])) {
            $query->where('name', 'like', '%'.$input['search'].'%');
        }

        $templates = $query->get()->map(fn($t) => [
            'name'     => $t->name,
            'language' => $t->language,
            'category' => $t->category,
            'status'   => $t->status,
        ]);

        if ($templates->isEmpty()) {
            return [['type' => 'text', 'text' => 'No approved templates found.']];
        }

        return [['type' => 'text', 'text' => json_encode($templates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
