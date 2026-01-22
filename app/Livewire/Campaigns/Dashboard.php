<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public $campaignId;
    public $statusLine = '';

    public function mount($campaign)
    {
        $this->campaignId = $campaign; // Handle both ID and Model if needed, but route pass ID
        if ($campaign instanceof Campaign) {
            $this->campaignId = $campaign->id;
        }
    }

    #[Computed]
    public function campaign()
    {
        return Campaign::findOrFail($this->campaignId);
    }

    #[Computed]
    public function metrics()
    {
        $campaign = $this->campaign;
        return [
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->del_count,
            'read' => $campaign->read_count,
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
            'total' => $campaign->total_contacts,
        ];
    }

    #[On('echo-private:campaign.{campaignId}.progress,progress.updated')]
    public function onProgressUpdate($data)
    {
        $this->dispatch('$refresh');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.campaigns.dashboard');
    }
}
