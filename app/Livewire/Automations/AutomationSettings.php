<?php

namespace App\Livewire\Automations;

use Livewire\Component;
use Livewire\Attributes\Layout;

class AutomationSettings extends Component
{
    public $away_message;
    public $away_message_enabled;
    public $timezone;
    public $business_hours; // JSON string for now

    public function mount()
    {
        $team = auth()->user()->currentTeam;
        $this->away_message = $team->away_message;
        $this->away_message_enabled = $team->away_message_enabled;
        $this->timezone = $team->timezone;
        $this->business_hours = json_encode($team->business_hours, JSON_PRETTY_PRINT);
    }

    public function save()
    {
        $this->validate([
            'away_message' => 'nullable|string',
            'away_message_enabled' => 'boolean',
            'timezone' => 'required|string',
            'business_hours' => 'nullable|json',
        ]);

        $team = auth()->user()->currentTeam;
        $team->update([
            'away_message' => $this->away_message,
            'away_message_enabled' => $this->away_message_enabled,
            'timezone' => $this->timezone,
            'business_hours' => json_decode($this->business_hours, true),
        ]);

        session()->flash('message', 'Settings saved successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.automations.automation-settings');
    }
}
