<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantService
{
    /**
     * Create a new tenant (Team + Owner User).
     */
    public function create(array $data, ?User $creator = null)
    {
        return DB::transaction(function () use ($data, $creator) {
            // 1. Create Owner User
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
            ]);

            // 2. Create Team
            $team = Team::forceCreate([
                'user_id' => $user->id,
                'name' => $data['company_name'],
                'personal_team' => false,
                'subscription_plan' => $data['plan'],
                'subscription_status' => 'active',
                'subscription_ends_at' => now()->addMonth(),
            ]);

            // 3. Attach User to Team
            $user->teams()->attach($team, ['role' => 'admin']);
            $user->forceFill(['current_team_id' => $team->id])->save();

            // 4. Handle Offers
            $this->handleWelcomeOffer($team);

            // 5. Audit & Logs
            Log::info('Tenant created', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'owner_email' => $user->email,
                'plan' => $data['plan'],
                'created_by' => $creator ? $creator->email : 'system',
            ]);

            return $team;
        });
    }

    /**
     * Handle initial welcome offer eligibility.
     */
    protected function handleWelcomeOffer(Team $team)
    {
        $eligibilitySvc = app(OfferEligibilityService::class);
        $offerApplied = $eligibilitySvc->isEligible($team);

        if ($offerApplied) {
            $credit = (float) get_setting('offer_initial_credit', 5.00);
            if ($credit > 0) {
                app(BillingService::class)->deposit(
                    $team,
                    $credit,
                    'Welcome Gift (Launch Offer)'
                );
            }
            $eligibilitySvc->markClaimed($team);
        }
    }

    public function getTenantId(): ?int
    {
        return auth()->user()?->currentTeam?->id;
    }

    public function getWabaId(): ?string
    {
        return auth()->user()?->currentTeam?->whatsapp_business_account_id;
    }

    public function getPhoneNumberId(): ?string
    {
        return auth()->user()?->currentTeam?->whatsapp_phone_number_id;
    }

    public function getAccessToken(): ?string
    {
        return auth()->user()?->currentTeam?->whatsapp_access_token;
    }

    public function isConnected(): bool
    {
        return (bool) auth()->user()?->currentTeam?->whatsapp_connected;
    }

    public function getUserRole(): ?string
    {
        $user = auth()->user();
        if (! $user || ! $user->currentTeam) {
            return null;
        }

        $role = $user->teamRole($user->currentTeam);

        return $role ? $role->key : null;
    }
}
