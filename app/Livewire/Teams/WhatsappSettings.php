<?php

namespace App\Livewire\Teams;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WhatsappSettings extends Component
{
    // Business Settings
    public $timezone = 'UTC';
    public $awayMessageEnabled = false;
    public $awayMessage = 'We are currently closed. We will get back to you soon.';

    // Default Hours: Mon-Fri 09-17
    public $openTime = '09:00';
    public $closeTime = '17:00';

    public function mount()
    {
        $team = Auth::user()->currentTeam;

        $this->timezone = $team->timezone ?? 'UTC';
        $this->awayMessageEnabled = $team->away_message_enabled;
        $this->awayMessage = $team->away_message;

        // Load first day's hours as default
        $hours = $team->business_hours;
        if (isset($hours['mon'])) {
            $this->openTime = $hours['mon'][0];
            $this->closeTime = $hours['mon'][1];
        }
    }

    public function save()
    {
        $team = Auth::user()->currentTeam;

        // Construct Business Hours (Simple Mon-Fri for MVP)
        $hours = [];
        foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $day) {
            $hours[$day] = [$this->openTime, $this->closeTime];
        }

        $team->forceFill([
            'timezone' => $this->timezone,
            'away_message_enabled' => $this->awayMessageEnabled,
            'away_message' => $this->awayMessage,
            'business_hours' => $hours,
        ])->save();

        session()->flash('message', 'Behavior settings saved successfully.');
    }

    public function getTimezonesProperty()
    {
        return \DateTimeZone::listIdentifiers();
    }

    public function render()
    {
        return view('livewire.teams.whatsapp-settings', [
            'timezones' => $this->timezones
        ]);
    }
}
