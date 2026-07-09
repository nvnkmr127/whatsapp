<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Services\Mcp\McpTool;
use App\Services\OutboundPreflightService;
use App\Services\WhatsAppService;

class SendTemplateTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'send_template',
            'description' => 'Send an approved WhatsApp template message. Use get_templates to discover available templates and their parameter count.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'            => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'template_name' => ['type' => 'string', 'description' => 'Exact template name as registered in WhatsApp'],
                    'language'      => ['type' => 'string', 'description' => 'Language code, e.g. en_US', 'default' => 'en_US'],
                    'body_params'   => [
                        'type'        => 'array',
                        'items'       => ['type' => 'string'],
                        'description' => 'Ordered list of body variable values ({{1}}, {{2}}, ...)',
                        'default'     => [],
                    ],
                ],
                'required' => ['to', 'template_name'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $preflight = app(OutboundPreflightService::class)->authorize($team, 'send_message', 'message_limit');
        if (! $preflight['allowed']) {
            throw new \Exception("[{$preflight['code']}] {$preflight['reason']}");
        }

        $response = app(WhatsAppService::class)->setTeam($team)->sendTemplate(
            $input['to'],
            $input['template_name'],
            $input['language'] ?? 'en_US',
            $input['body_params'] ?? []
        );

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Template sent. WhatsApp ID: {$wamId}"]];
    }
}
