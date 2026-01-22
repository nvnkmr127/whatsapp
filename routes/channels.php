<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Conversations Channel (for list updates)
Broadcast::channel('conversations', function ($user) {
    return true; // For MVP. TODO: Scope to Team.
});

// Single Conversation Channel (for messages)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $conversation && $conversation->team_id === $user->currentTeam->id;
});

// Presence Channel availability for typing indicators & locks
Broadcast::channel('presence-conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    if ($conversation && $conversation->team_id === $user->currentTeam->id) {
        return ['id' => $user->id, 'name' => $user->name];
    }
});

// Campaign Progress Channel
Broadcast::channel('campaign.{campaignId}.progress', function ($user, $campaignId) {
    $campaign = \App\Models\Campaign::find($campaignId);
    return $campaign && $campaign->team_id === $user->current_team_id;
});
