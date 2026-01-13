<?php

namespace App\Livewire\Teams;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WhatsappSettings extends Component
{
    public $phoneNumberId;
    public $wabaId;
    public $accessToken;
    public $connectionStatus = 'unknown'; // unknown, success, failed
    public $errorMessage = '';

    // Business Settings
    public $timezone = 'UTC';
    public $awayMessageEnabled = false;
    public $awayMessage = 'We are currently closed. We will get back to you soon.';
    // Simplified Hours: assume same for Mon-Fri for MVP UI, or simple JSON structure
    // Let's do Mon-Fri 09-17 default
    public $openTime = '09:00';
    public $closeTime = '17:00';

    public function mount()
    {
        $team = Auth::user()->currentTeam;
        $this->phoneId = $team->whatsapp_phone_number_id;
        $this->wabaId = $team->whatsapp_business_account_id;
        // Decrypt if it was encrypted
        $this->accessToken = $team->whatsapp_access_token;

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
        $this->validate([
            'phoneId' => 'required',
            'wabaId' => 'required',
            'accessToken' => 'required',
        ]);

        $team = Auth::user()->currentTeam;

        // Construct Business Hours (Simple Mon-Fri for MVP)
        $hours = [];
        foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $day) {
            $hours[$day] = [$this->openTime, $this->closeTime];
        }

        $team->forceFill([
            'whatsapp_phone_number_id' => $this->phoneId,
            'whatsapp_business_account_id' => $this->wabaId,
            'whatsapp_access_token' => $this->accessToken,
            'whatsapp_connected' => true,
            'timezone' => $this->timezone,
            'away_message_enabled' => $this->awayMessageEnabled,
            'away_message' => $this->awayMessage,
            'business_hours' => $hours,
        ])->save();

        session()->flash('message', 'Settings saved successfully.');
    }

    public function testConnection(WhatsAppService $whatsapp)
    {
        $team = Auth::user()->currentTeam;

        try {
            // Attempt to fetch profile
            $result = $whatsapp->setTeam($team)->getBusinessProfile();

            if ($result['success']) {
                $team->update(['whatsapp_connected' => true]);
                $this->connectionStatus = 'success';
                $this->errorMessage = '';
            } else {
                $team->update(['whatsapp_connected' => false]);
                $this->connectionStatus = 'failed';
                $this->errorMessage = 'Meta API refused connection. Check credentials.';
            }
        } catch (\Exception $e) {
            $this->connectionStatus = 'failed';
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.teams.whatsapp-settings');
    }
}
