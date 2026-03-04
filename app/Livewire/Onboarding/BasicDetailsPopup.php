<?php

namespace App\Livewire\Onboarding;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class BasicDetailsPopup extends Component
{
    public $isOpen = false;
    public $phone;
    public $company_name;
    public $address;
    public $email;

    public function mount()
    {
        \Illuminate\Support\Facades\Log::debug('BasicDetailsPopup::mount started');
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Auto-assign current team if missing (e.g. after fresh registration or data inconsistency)
        if (is_null($user->current_team_id)) {
            $personalTeam = $user->ownedTeams()->first() ?? $user->allTeams()->first();
            if ($personalTeam) {
                $user->forceFill([
                    'current_team_id' => $personalTeam->id,
                ])->save();
                $user->refresh();
            }
        }

        $team = $user->currentTeam;

        // Open if company name is missing OR if team looks like default
        // We no longer force open on phone=null because it's optional
        // SUPER ADMINS are excluded from this onboarding flow.
        if (!$user->isSuperAdmin() && (empty($user->company_name) || ($team && str_ends_with($team->name, "'s Team")))) {
            $this->isOpen = true;
            \Illuminate\Support\Facades\Log::debug('BasicDetailsPopup opening: company missing or default team name', [
                'company' => $user->company_name,
                'team_name' => $team?->name
            ]);
        }

        $this->phone = $user->phone;
        $this->email = $user->email;
        $this->company_name = $user->company_name ?? ($team ? $team->name : '');
        $this->address = $user->address ?? ($team ? $team->billing_address : '');

        // If it looks like default team name, clear it so they fill their actual business name
        if ($this->company_name && str_ends_with($this->company_name, "'s Team")) {
            $this->company_name = '';
        }
    }

    public function save()
    {
        try {
            \Illuminate\Support\Facades\Log::info('BasicDetailsPopup::save started', [
                'phone' => $this->phone,
                'company' => $this->company_name,
                'email' => $this->email,
                'address' => $this->address,
                'user_id' => Auth::id()
            ]);

            $user = Auth::user();

            $this->validate([
                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                    \Illuminate\Validation\Rule::unique(\App\Models\User::class, 'phone')->ignore($user->id),
                ],
                'company_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'address' => 'nullable|string|max:500',
            ]);

            $user->forceFill([
                'phone' => $this->phone,
                'email' => $this->email,
                'company_name' => $this->company_name,
                'address' => $this->address,
            ])->save();

            // Ensure user has at least one team (Critical Fallback)
            if ($user->allTeams()->isEmpty()) {
                $team = \App\Models\Team::forceCreate([
                    'user_id' => $user->id,
                    'name' => $this->company_name ?: (explode(' ', $user->name, 2)[0] . "'s Team"),
                    'personal_team' => true,
                    'subscription_status' => 'trial',
                    'trial_ends_at' => now()->addMonths(6),
                ]);
                $user->ownedTeams()->save($team);
                $user->forceFill(['current_team_id' => $team->id])->save();
                $user->refresh();
                \Illuminate\Support\Facades\Log::info('Created missing personal team for user during onboarding');
            }

            \Illuminate\Support\Facades\Log::info('User details saved');

            // Ensure current team is assigned if still missing
            if (!$user->currentTeam && $user->allTeams()->isNotEmpty()) {
                $user->forceFill(['current_team_id' => $user->allTeams()->first()->id])->save();
                $user->refresh();
            }

            $team = $user->currentTeam;
            if ($team) {
                $team->forceFill([
                    'name' => $this->company_name,
                    'billing_address' => $this->address,
                ])->save();
                \Illuminate\Support\Facades\Log::info('Team details saved');
            }

            $this->isOpen = false;

            \Illuminate\Support\Facades\Log::info('Redirecting to teams.whatsapp_config');

            return redirect()->route('teams.whatsapp_config');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('BasicDetailsPopup validation failed', $e->errors());
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('BasicDetailsPopup::save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Something went wrong while saving your details.');
        }
    }

    public function render()
    {
        return view('livewire.onboarding.basic-details-popup');
    }
}
