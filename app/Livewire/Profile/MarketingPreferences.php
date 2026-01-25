<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class MarketingPreferences extends Component
{
    public $marketing_opt_in;

    public function mount()
    {
        $this->marketing_opt_in = Auth::user()->marketing_opt_in;
    }

    public function updateMarketingPreferences()
    {
        $user = Auth::user();

        $user->marketing_opt_in = $this->marketing_opt_in;

        if (!$user->unsubscribe_token) {
            $user->unsubscribe_token = Str::random(40);
        }

        $user->save();

        $this->dispatch('saved');
        $this->dispatch('notify', [
            'message' => 'Marketing preferences updated successfully.',
            'style' => 'success'
        ]);
    }

    public function render()
    {
        return view('livewire.profile.marketing-preferences');
    }
}
