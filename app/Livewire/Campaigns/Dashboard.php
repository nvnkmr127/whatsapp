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
        $sentCount = $campaign->messages()->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $failedCount = $campaign->messages()->where('status', 'failed')->count();
        
        return [
            'sent' => max($campaign->sent_count, $sentCount),
            'delivered' => $campaign->del_count,
            'read' => $campaign->read_count,
            'failed' => $failedCount,
            'total' => $campaign->total_contacts,
        ];
    }

    #[Computed]
    public function recentMessages()
    {
        return \App\Models\Message::where('campaign_id', $this->campaignId)
            ->with(['contact:id,name,phone_number'])
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function speed()
    {
        $campaign = $this->campaign;
        if (!$campaign->started_at || $campaign->status === 'completed') return 0;
        
        // Use last 5 minutes from messages table for more accurate "current" speed
        $sentInLast5Min = \App\Models\Message::where('campaign_id', $campaign->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereIn('status', ['sent', 'delivered', 'read'])
            ->count();
            
        return round($sentInLast5Min / 5, 1); // msgs per minute
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
