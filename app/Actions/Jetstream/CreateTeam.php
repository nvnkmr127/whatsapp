<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;

class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        AddingTeam::dispatch($user);

        $user->switchTeam($team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'personal_team' => false,
            'subscription_plan' => 'basic', // Default plan
        ]));

        // Initialize Wallet and Add Credits if applicable
        $defaultPlan = \App\Models\Plan::where('name', 'basic')->first();
        $initialBalance = $defaultPlan ? $defaultPlan->initial_wallet_balance : 0;

        \App\Models\TeamWallet::create([
            'team_id' => $team->id,
            'balance' => $initialBalance,
            'currency' => function_exists('get_setting') ? get_setting('currency', 'USD') : 'USD',
        ]);

        if ($initialBalance > 0) {
            \App\Models\TeamTransaction::create([
                'team_id' => $team->id,
                'amount' => $initialBalance,
                'type' => 'bonus',
                'description' => 'Initial Plan Credits',
            ]);
        }

        return $team;
    }
}
