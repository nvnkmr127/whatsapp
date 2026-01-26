<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Team-level channel for broad updates (new messages across all contacts)
Broadcast::channel('teams.{teamId}', function ($user, $teamId) {
    return (int) $user->currentTeam->id === (int) $teamId;
});

// Conversations Channel (for list updates - generic name but scoped in listener)
Broadcast::channel('conversations', function ($user) {
    return $user->currentTeam !== null;
});

// Single Conversation Channel (Presence Capable)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);

    // Debug Logging
    \Log::info("Broadcast Auth Check: conversation.$conversationId", [
        'user_id' => $user->id,
        'user_team' => $user->current_team_id, // Use FK to avoid null prop error if relation fails
        'conversation_exists' => (bool) $conversation,
        'conv_team' => $conversation?->team_id
    ]);

    if ($conversation && (int) $conversation->team_id === (int) $user->current_team_id) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return false;
});

// Campaign Progress Channel
Broadcast::channel('campaign.{campaignId}.progress', function ($user, $campaignId) {
    $campaign = \App\Models\Campaign::find($campaignId);
    return $campaign && $campaign->team_id === $user->current_team_id;
});
