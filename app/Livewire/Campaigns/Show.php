<?php

namespace App\Livewire\Campaigns;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Campaign;

class Show extends Component
{
    use \Livewire\WithPagination;

    public $campaignId;

    public function mount($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    #[Computed]
    public function campaign()
    {
        return Campaign::with('messages')->findOrFail($this->campaignId);
    }

    #[Computed]
    public function campaignStats()
    {
        $campaign = $this->campaign;

        $sentCount = $campaign->messages()->count();
        $deliveredCount = $campaign->messages()->whereIn('status', ['delivered', 'read'])->count();
        $readCount = $campaign->messages()->where('status', 'read')->count();

        return [
            'total' => $campaign->audience_count ?: $campaign->total_contacts,
            'sent' => $sentCount,
            'delivered' => $deliveredCount,
            'read' => $readCount,
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
            'delivery_rate' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100) : 0,
            'read_rate' => $deliveredCount > 0 ? round(($readCount / $deliveredCount) * 100) : 0,
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        if ($this->campaign->team_id !== auth()->user()->currentTeam->id) {
            abort(403);
        }

        $messages = $this->campaign->messages()->latest()->paginate(20);

        return view('livewire.campaigns.show', [
            'messages' => $messages
        ]);
    }
}
