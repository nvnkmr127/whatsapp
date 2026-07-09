<?php

namespace App\Services\Mcp\Tools;

use App\Models\Campaign;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class ListCampaignsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'list_campaigns',
            'description' => 'List broadcast campaigns for this tenant with their status and stats.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'status' => [
                        'type'        => 'string',
                        'enum'        => ['draft', 'scheduled', 'running', 'completed', 'failed'],
                        'description' => 'Filter by campaign status',
                    ],
                    'limit' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 50],
                ],
                'required' => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $limit = min((int) ($input['limit'] ?? 20), 50);

        $query = Campaign::where('team_id', $team->id)->latest()->limit($limit);

        if (! empty($input['status'])) {
            $query->where('status', $input['status']);
        }

        $campaigns = $query->get()->map(fn($c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'status'      => $c->status,
            'scheduled_at'=> $c->scheduled_at?->toDateTimeString(),
            'sent_count'  => $c->sent_count ?? 0,
            'failed_count'=> $c->failed_count ?? 0,
        ]);

        if ($campaigns->isEmpty()) {
            return [['type' => 'text', 'text' => 'No campaigns found.']];
        }

        return [['type' => 'text', 'text' => json_encode($campaigns, JSON_PRETTY_PRINT)]];
    }
}
