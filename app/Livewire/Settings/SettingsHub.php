<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Component;

class SettingsHub extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.settings.settings-hub');
    }
}
