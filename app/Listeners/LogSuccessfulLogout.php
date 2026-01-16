<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;
        if ($user) {
            /** @var \App\Models\User $user */
            audit('auth.logout', "User '{$user->name}' logged out.", $user);
        }
    }
}
