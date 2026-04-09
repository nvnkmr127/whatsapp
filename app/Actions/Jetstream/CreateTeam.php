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

        // Inherit offer limits if user's personal team has them (Offer Extension)
        $personalTeam = $user->personalTeam();
        $offerSnapshot = $personalTeam ? $personalTeam->offer_snapshot : null;

        $team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'personal_team' => false,
            'subscription_plan' => 'basic', // Default plan
            'offer_snapshot' => $offerSnapshot, // Propagate offer limits to new teams
        ]);

        $user->switchTeam($team);

        // Initialize Wallet
        $initialBalance = 0;

        // If user is on an offer (has snapshot), they might get bonus credits per team OR just limits.
        // Usually, bonus credits are a one-time signup gift, not per-team.
        // However, if the PLAN has an initial balance, we honor that.
        $defaultPlan = \App\Models\Plan::where('name', 'basic')->first();
        if ($defaultPlan) {
            $initialBalance = $defaultPlan->initial_wallet_balance;
        }

        $wallet = \App\Models\TeamWallet::create([
            'team_id' => $team->id,
            'balance' => $initialBalance,
        ]); // Removed currency field as it might not exist in schema yet or handled globally

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
