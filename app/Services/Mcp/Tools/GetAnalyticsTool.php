<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class GetAnalyticsTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_analytics',
            'description' => 'Fetch WhatsApp Business Account analytics from Meta (conversation counts, messaging stats) for a date range.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'start'       => ['type' => 'string', 'description' => 'Start date in Y-m-d format (e.g. 2025-01-01)'],
                    'end'         => ['type' => 'string', 'description' => 'End date in Y-m-d format (e.g. 2025-01-31)'],
                    'granularity' => [
                        'type'    => 'string',
                        'enum'    => ['DAILY', 'MONTHLY'],
                        'default' => 'DAILY',
                    ],
                ],
                'required' => ['start', 'end'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $response = app(WhatsAppService::class)->setTeam($team)->getAnalytics(
            $input['start'],
            $input['end'],
            $input['granularity'] ?? 'DAILY'
        );

        if (! ($response['success'] ?? false)) {
            throw new \Exception('Failed to fetch analytics: '.json_encode($response['error'] ?? 'Unknown error'));
        }

        return [['type' => 'text', 'text' => json_encode($response['data'], JSON_PRETTY_PRINT)]];
    }
}
