<?php

namespace App\Services\Mcp\Tools;

use App\Models\Team;
use App\Services\Mcp\McpTool;
use App\Services\AI\AIProviderManager;

class GetAiSuggestReplyTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'get_ai_suggest_reply',
            'description' => 'Use the team\'s configured AI (GPT-4, Claude, etc.) to generate a suggested reply for a conversation, based on the last 10 messages. Returns suggested text only — does NOT send it. The agent can review and edit before sending.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID to generate a reply for'],
                ],
                'required' => ['conversation_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $conversation = \App\Models\Conversation::where('id', $input['conversation_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        $messages = \App\Models\Message::where('contact_id', $conversation->contact_id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        if ($messages->isEmpty()) {
            throw new \Exception('No messages found in this conversation to generate a suggestion from.');
        }

        $transcript = $messages->map(function ($msg) {
            $role = $msg->direction === 'inbound' ? 'Customer' : 'Agent';
            return "[{$role}]: " . ($msg->content ?? '(media)');
        })->join("\n");

        $persona = get_setting("ai_persona_{$team->id}")
            ?? 'You are a helpful and professional customer service agent.';

        $prompt = "{$persona}\n\nConversation:\n{$transcript}\n\n"
            . 'Based on the above conversation, generate a concise and professional reply for the Agent to send next. '
            . 'Return ONLY the reply text, nothing else.';

        $suggestion = AIProviderManager::summarize($team, $prompt);

        return [['type' => 'text', 'text' => trim($suggestion)]];
    }
}
