<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Livewire\Component;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public $campaign;
    public $metrics = [];
    public $statusLine = '';

    public function mount(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->refreshMetrics();
    }

    #[On('echo-private:campaign.{campaign.id}.progress,progress.updated')]
    public function onProgressUpdate($data)
    {
        // $data contains ['metrics' => [...], 'status' => '...']
        $this->metrics = $data['metrics'];
        $this->campaign->status = $data['status'];
    }

    public function refreshMetrics()
    {
        $this->metrics = [
            'sent' => $this->campaign->sent_count,
            'delivered' => $this->campaign->messages()->where('status', 'delivered')->count(),
            'read' => $this->campaign->messages()->where('status', 'read')->count(),
            'failed' => $this->campaign->messages()->where('status', 'failed')->count(),
            'total' => $this->campaign->lastSnapshot ? $this->campaign->lastSnapshot->audience_count : 0,
        ];
    }

    public function render()
    {
        return view('livewire.campaigns.dashboard');
    }
}
