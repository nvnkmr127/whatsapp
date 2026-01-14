<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookSubscription;

class WebhookSubscriptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-settings');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $user->currentTeam->id === $webhookSubscription->team_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasTeamPermission($user->currentTeam, 'manage-settings');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $user->currentTeam->id === $webhookSubscription->team_id
            && $user->hasTeamPermission($user->currentTeam, 'manage-settings');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $user->currentTeam->id === $webhookSubscription->team_id
            && $user->hasTeamPermission($user->currentTeam, 'manage-settings');
    }
}
