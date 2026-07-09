<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;

class ToggleAiTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'toggle_ai',
            'description' => 'Enable or disable the AI bot for a specific contact/conversation. When disabled, all bot auto-replies stop and the conversation is handed to a human agent.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID'],
                    'enabled'         => ['type' => 'boolean', 'description' => 'true = enable AI, false = pause AI (human takeover)'],
                ],
                'required' => ['conversation_id', 'enabled'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $conversation = \App\Models\Conversation::where('id', $input['conversation_id'])
            ->where('team_id', $team->id)
            ->with('contact')
            ->firstOrFail();

        $contact  = $conversation->contact;
        $enabled  = (bool) $input['enabled'];
        $paused   = ! $enabled;

        $contact->update([
            'is_bot_paused'    => $paused,
            'bot_paused_at'    => $paused ? now() : null,
            'bot_paused_reason'=> $paused ? 'Paused via MCP' : null,
        ]);

        $state = $enabled ? 'enabled' : 'paused (human takeover)';

        return [['type' => 'text', 'text' => "AI bot {$state} for conversation #{$conversation->id} (contact: {$contact->name})."]];
    }
}
