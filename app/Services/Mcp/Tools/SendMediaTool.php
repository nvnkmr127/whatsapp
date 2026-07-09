<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\OutboundPreflightService;
use App\Services\WhatsAppService;

class SendMediaTool implements McpTool
{
    private const ALLOWED_TYPES = ['image', 'video', 'audio', 'document'];

    public function schema(): array
    {
        return [
            'name'        => 'send_media',
            'description' => 'Send a media file (image, video, audio, or document) via WhatsApp.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'to'      => ['type' => 'string', 'description' => 'Recipient phone in E.164 format'],
                    'type'    => ['type' => 'string', 'enum' => self::ALLOWED_TYPES, 'description' => 'Media type'],
                    'url'     => ['type' => 'string', 'description' => 'Publicly accessible URL of the media file'],
                    'caption' => ['type' => 'string', 'description' => 'Optional caption (supported for image, video, document)'],
                ],
                'required' => ['to', 'type', 'url'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        if (! in_array($input['type'], self::ALLOWED_TYPES, true)) {
            throw new \Exception("Invalid media type '{$input['type']}'. Must be one of: ".implode(', ', self::ALLOWED_TYPES));
        }

        $preflight = app(OutboundPreflightService::class)->authorize($team, 'send_message', 'message_limit');
        if (! $preflight['allowed']) {
            throw new \Exception("[{$preflight['code']}] {$preflight['reason']}");
        }

        $response = app(WhatsAppService::class)->setTeam($team)->sendMedia(
            $input['to'],
            $input['type'],
            $input['url'],
            $input['caption'] ?? null
        );

        $wamId = $response['data']['messages'][0]['id'] ?? null;

        return [['type' => 'text', 'text' => "Media sent. Type: {$input['type']}. WhatsApp ID: {$wamId}"]];
    }
}
