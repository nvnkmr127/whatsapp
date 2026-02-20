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
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $team = $user->currentTeam;

        // Open if phone is missing OR if team looks like default
        if (is_null($user->phone) || ($team && str_ends_with($team->name, "'s Team"))) {
            $this->isOpen = true;
        }

        $this->phone = $user->phone;
        $this->email = $user->email;
        $this->company_name = $user->company_name ?? ($team ? $team->name : '');
        $this->address = $user->address ?? ($team ? $team->billing_address : '');

        // If it looks like default team name, clear it so they fill their actual business name
        if (str_ends_with($this->company_name, "'s Team")) {
            $this->company_name = '';
        }
    }

    public function save()
    {
        \Illuminate\Support\Facades\Log::info('BasicDetailsPopup::save started', [
            'phone' => $this->phone,
            'company' => $this->company_name,
            'email' => $this->email,
            'address' => $this->address,
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

        \Illuminate\Support\Facades\Log::info('User details saved');

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
    }

    public function render()
    {
        return view('livewire.onboarding.basic-details-popup');
    }
}
