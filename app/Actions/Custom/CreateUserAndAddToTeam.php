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
        // Normalize the phone up front so lookup and storage use standard format.
        $rawPhone = $input['phone'] ?? '';
        try {
            $input['phone'] = PhoneNumberHelper::normalize($rawPhone);
        } catch (\Exception $e) {
            // Leave as-is; validator below reports format errors if invalid.
        }

        $phone = $input['phone'];

        // Find existing user by phone if any (exact normalized, raw, or suffix match)
        $existingUser = User::where('phone', $phone)
            ->orWhere('phone', $rawPhone)
            ->first();

        if (! $existingUser && strlen($phone) >= 10) {
            $suffix = substr($phone, -10);
            $existingUser = User::where('phone', 'like', "%{$suffix}")->first();
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'role' => Jetstream::hasRoles()
                ? ['required', 'string', new Role]
                : null,
        ])->after(function ($validator) use ($team, $existingUser) {
            // Same plan gate as before — otherwise adding members bypasses the agent limit.
            if (! $team->canAccess('add_agent')) {
                $validator->errors()->add('phone', __('This team has reached its agent limit for the current plan.'));
            }

            // If user already exists and is currently a member of this team, error out.
            if ($existingUser && $team->hasUser($existingUser)) {
                $validator->errors()->add('phone', __('This user already belongs to the team.'));
            }
        })->validateWithBag('createUser');

        DB::transaction(function () use ($team, $input, $existingUser) {
            if ($existingUser) {
                $user = $existingUser;
                if (empty($user->name)) {
                    $user->forceFill(['name' => $input['name']])->save();
                }
                if (empty($user->email_verified_at)) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }
            } else {
                $user = User::create([
                    'name' => $input['name'],
                    'phone' => $input['phone'],
                    'email_verified_at' => now(),
                ]);
            }

            // Register the phone as an OTP identity so login resolves it directly.
            UserIdentity::updateOrCreate(
                ['user_id' => $user->id, 'provider' => 'phone_otp'],
                ['provider_id' => $user->phone, 'last_login_at' => now()]
            );

            if (! $team->hasUser($user)) {
                $team->users()->attach(
                    $user,
                    [
                        'role' => $input['role'],
                        'receives_tickets' => $input['role'] === 'agent',
                    ]
                );
            }

            if (! $user->current_team_id) {
                $user->switchTeam($team);
            }
        });
    }
}
