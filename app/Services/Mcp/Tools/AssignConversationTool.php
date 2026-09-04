<?php

namespace App\Services\Mcp\Tools;

use App\Models\Contact;
use App\Models\Team;
use App\Services\Mcp\McpTool;

class AssignConversationTool implements McpTool
{
    public function schema(): array
    {
        return [
            'name'        => 'assign_conversation',
            'description' => 'Assign a conversation to a team member by user ID. Use get_team_members to discover available user IDs.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'conversation_id' => ['type' => 'integer', 'description' => 'Conversation ID'],
                    'user_id'         => ['type' => 'integer', 'description' => 'User ID of the team member to assign'],
                ],
                'required' => ['conversation_id', 'user_id'],
            ],
        ];
    }

    public function handle(array $input, Team $team): array
    {
        $conversation = \App\Models\Conversation::where('id', $input['conversation_id'])
            ->where('team_id', $team->id)
            ->firstOrFail();

        // Validate user belongs to this team (includes team owner)
        $member = $team->allUsers()->firstWhere('id', (int) $input['user_id']);
        if (! $member) {
            throw new \Exception("User #{$input['user_id']} is not a member of this team.");
        }

        $conversation->update(['assigned_to' => $input['user_id']]);

        return [['type' => 'text', 'text' => "Conversation #{$conversation->id} assigned to {$member->name}."]];
    }
}
