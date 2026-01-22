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
