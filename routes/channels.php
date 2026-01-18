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

// Team Channel (for global team events like MessageReceived)
Broadcast::channel('teams.{teamId}', function ($user, $teamId) {
    return (int) $user->current_team_id === (int) $teamId;
});
