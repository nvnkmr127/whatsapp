<?php

namespace App\Livewire\Teams;

use App\Models\WhatsAppHealthAlert;
use Livewire\Component;

class WhatsappAlerts extends Component
{
    public $alerts;

    public function mount()
    {
        $this->loadAlerts();
    }

    public function loadAlerts()
    {
        $this->alerts = WhatsAppHealthAlert::where('team_id', auth()->user()->currentTeam->id)
            ->where('acknowledged', false)
            ->whereNull('resolved_at')
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function acknowledge($alertId)
    {
        $alert = WhatsAppHealthAlert::find($alertId);
        if ($alert && $alert->team_id === auth()->user()->currentTeam->id) {
            $alert->acknowledge(auth()->user());
            $this->loadAlerts();
            $this->dispatch('alert-acknowledged');
        }
    }

    public function render()
    {
        return view('livewire.teams.whatsapp-alerts');
    }
}
