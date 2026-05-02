<?php

namespace App\Policies;

use App\Models\MessageBot;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageBotPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-workflows');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MessageBot $messageBot)
    {
        return $user->current_team_id === $messageBot->team_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-workflows');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MessageBot $messageBot)
    {
        return $user->current_team_id === $messageBot->team_id && 
               $user->hasTeamPermission($user->currentTeam, 'manage-workflows');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MessageBot $messageBot)
    {
        return $user->current_team_id === $messageBot->team_id && 
               $user->hasTeamPermission($user->currentTeam, 'manage-workflows');
    }
}
