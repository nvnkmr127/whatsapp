<?php

namespace App\Actions\Custom;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;

class CreateUserAndAddToTeam
{
    /**
     * Create a new user and add them to the team.
     *
     * @param  \App\Models\User  $creator
     * @param  \App\Models\Team  $team
     * @param  array  $input
     * @return void
     */
    public function create(User $creator, Team $team, array $input)
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => Jetstream::hasRoles()
                ? ['required', 'string', new Role]
                : null,
        ])->validateWithBag('createUser');

        DB::transaction(function () use ($creator, $team, $input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'email_verified_at' => now(), // Auto-verify since admin created it
            ]);

            $team->users()->attach(
                $user,
                [
                    'role' => $input['role'],
                    'receives_tickets' => $input['role'] === 'agent'
                ]
            );

            // Optional: Create personal team for the user if required by app logic
            // But since they are part of this team, maybe strict requirement?
            // Jetstream usually expects every user to have a personal team or current team.
            $this->createPersonalTeam($user);

            $user->switchTeam($team);
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createPersonalTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));
    }
}
