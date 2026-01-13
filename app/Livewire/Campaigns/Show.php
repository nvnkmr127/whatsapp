<?php

namespace App\Livewire\Campaigns;

use Livewire\Component;
use Livewire\Attributes\Layout;

class Show extends Component
{
    use \Livewire\WithPagination;

    public $campaignId;
    public $stats = [];

    public function mount($campaignId)
    {
        $this->campaignId = $campaignId;
        $this->refreshStats();
    }

    public function refreshStats()
    {
        $campaign = \App\Models\Campaign::findOrFail($this->campaignId);

        // Ensure user owns team
        if ($campaign->team_id !== auth()->user()->currentTeam->id) {
            abort(403);
        }

        $this->stats = [
            'total' => $campaign->total_contacts,
            // Messages linked to campaign
            'sent' => $campaign->messages()->count(), // Actually dispatched
            'delivered' => $campaign->messages()->whereIn('status', ['delivered', 'read'])->count(),
            'read' => $campaign->messages()->where('status', 'read')->count(),
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $campaign = \App\Models\Campaign::findOrFail($this->campaignId);
        $messages = $campaign->messages()->latest()->paginate(20);

        return view('livewire.campaigns.show', [
            'campaign' => $campaign,
            'messages' => $messages
        ]);
    }
}
