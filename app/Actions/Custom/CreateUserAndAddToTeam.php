<?php

namespace App\Actions\Custom;

use App\Helpers\PhoneNumberHelper;
use App\Models\Team;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Rules\Role;

class CreateUserAndAddToTeam
{
    /**
     * Create a new team member from name + phone and add them to the team.
     * The member signs in via phone OTP — no email or password is set here.
     *
     * @return void
     */
    public function create(User $creator, Team $team, array $input)
    {
        // Normalize the phone up front so uniqueness is checked against the stored form.
        try {
            $input['phone'] = PhoneNumberHelper::normalize($input['phone'] ?? '');
        } catch (\Exception $e) {
            // Leave as-is; the validator below reports the format error.
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'role' => Jetstream::hasRoles()
                ? ['required', 'string', new Role]
                : null,
        ])->after(function ($validator) use ($team) {
            // Same plan gate as before — otherwise adding members bypasses the agent limit.
            if (! $team->canAccess('add_agent')) {
                $validator->errors()->add('phone', __('This team has reached its agent limit for the current plan.'));
            }
        })->validateWithBag('createUser');

        DB::transaction(function () use ($team, $input) {
            $user = User::create([
                'name' => $input['name'],
                'phone' => $input['phone'],
                'email_verified_at' => now(),
            ]);

            // Register the phone as an OTP identity so login resolves it directly.
            UserIdentity::create([
                'user_id' => $user->id,
                'provider' => 'phone_otp',
                'provider_id' => $input['phone'],
            ]);

            $team->users()->attach(
                $user,
                [
                    'role' => $input['role'],
                    'receives_tickets' => $input['role'] === 'agent',
                ]
            );

            $user->switchTeam($team);
        });
    }
}
