<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class WhatsappTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true; // Any authenticated user in the team can view
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WhatsappTemplate $template)
    {
        return $user->current_team_id === $template->team_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-templates');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WhatsappTemplate $template)
    {
        return $user->current_team_id === $template->team_id && 
               $user->hasTeamPermission($user->currentTeam, 'manage-templates');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WhatsappTemplate $template)
    {
        return $user->current_team_id === $template->team_id && 
               $user->hasTeamPermission($user->currentTeam, 'manage-templates');
    }
}
