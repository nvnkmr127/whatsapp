<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\WhatsAppService;

class GetBusinessProfileTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_business_profile',
            'description' => 'Retrieve the WhatsApp Business Profile for this tenant (description, email, website, hours, etc.).',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [],
                'required'   => [],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $response = app(WhatsAppService::class)->setTeam($team)->getBusinessProfile();

        if (! ($response['success'] ?? false)) {
            throw new \Exception('Failed to fetch business profile: '.json_encode($response['error'] ?? 'Unknown error'));
        }

        return [['type' => 'text', 'text' => json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]];
    }
}
