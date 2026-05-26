<?php

namespace App\Policies;

use App\Models\Automation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutomationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-workflows') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Automation $automation)
    {
        return $user->current_team_id === $automation->team_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-workflows') || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Automation $automation)
    {
        return $user->current_team_id === $automation->team_id && 
               ($user->hasTeamPermission($user->currentTeam, 'manage-workflows') || $user->isSuperAdmin());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Automation $automation)
    {
        return $user->current_team_id === $automation->team_id && 
               ($user->hasTeamPermission($user->currentTeam, 'manage-workflows') || $user->isSuperAdmin());
    }
}
