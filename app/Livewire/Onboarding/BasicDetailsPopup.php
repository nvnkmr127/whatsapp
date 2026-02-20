<?php

namespace App\Livewire\Onboarding;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class BasicDetailsPopup extends Component
{
    public $isOpen = false;
    public $phone;
    public $company_name;

    public function mount()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $team = $user->currentTeam;

        if (is_null($user->phone)) {
            $this->isOpen = true;
        }

        $this->phone = $user->phone;
        $this->company_name = $team ? $team->name : '';

        // If it looks like default team name, clear it so they fill their actual business name
        if (str_ends_with($this->company_name, "'s Team")) {
            $this->company_name = '';
        }
    }

    public function save()
    {
        $this->validate([
            'phone' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->forceFill(['phone' => $this->phone])->save();

        $team = $user->currentTeam;
        if ($team) {
            $team->forceFill(['name' => $this->company_name])->save();
        }

        $this->isOpen = false;

        return redirect()->route('teams.whatsapp_config');
    }

    public function render()
    {
        return view('livewire.onboarding.basic-details-popup');
    }
}
