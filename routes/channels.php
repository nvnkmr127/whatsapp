<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Conversations Channel (for list updates)
Broadcast::channel('conversations', function ($user) {
    // Only allow if user belongs to the team (assuming team-based channel later, but for now global per app or specific to team)
    // Ideally: 'teams.{teamId}.conversations'
    return true; // For MVP. TODO: Scope to Team.
});

// Single Conversation Channel (for messages)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $conversation && $conversation->team_id === $user->currentTeam->id;
});
