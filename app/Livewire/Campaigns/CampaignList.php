<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('Campaigns')]
class CampaignList extends Component
{
    use WithPagination;

    public $search = '';
    public $confirmingDeletion = false;
    public $campaignIdToDelete = null;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-campaigns');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete($id)
    {
        $this->campaignIdToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-campaigns');
        $campaign = Campaign::where('team_id', auth()->user()->current_team_id)->find($this->campaignIdToDelete);
        if ($campaign) {
            $campaign->delete();
            $this->dispatch('notify', 'Campaign deleted successfully.');
        }
        $this->confirmingDeletion = false;
    }

    // Retargeting
    public $showRetargetModal = false;
    public $retargetingCriteria = 'not_read';
    public $selectedCampaignId = null;

    public function openRetargetModal($campaignId)
    {
        $this->selectedCampaignId = $campaignId;
        $this->showRetargetModal = true;
    }

    public function retarget()
    {
        $campaign = Campaign::where('team_id', auth()->user()->current_team_id)->findOrFail($this->selectedCampaignId);
        $query = \App\Models\CampaignDetail::where('campaign_id', $this->selectedCampaignId);

        switch ($this->retargetingCriteria) {
            case 'not_delivered':
                $query->whereIn('status', ['failed', 'pending', 'sent']);
                break;
            case 'not_read':
                $query->where('status', 'delivered');
                break;
            case 'read':
                $query->where('status', 'read');
                break;
            case 'failed':
                $query->where('status', 'failed');
                break;
        }

        $contactIds = $query->pluck('contact_id')->unique()->toArray();

        if (empty($contactIds)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No contacts found matching criteria.'
            ]);
            return;
        }

        return redirect()->route('campaigns.create', [
            'retarget_ids' => $contactIds,
            'default_name' => "Retarget: " . $campaign->name . " (" . str_replace('_', ' ', $this->retargetingCriteria) . ")"
        ]);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = Campaign::query();

        if ($this->search) {
            $query->where('campaign_name', 'like', '%' . $this->search . '%');
        }

        // Add team scope
        if (auth()->check() && auth()->user()->current_team_id) {
            $query->where('team_id', auth()->user()->current_team_id);
        }

        $campaigns = $query->latest()->paginate(10);

        // Module-Level Core Metrics
        $teamId = auth()->user()->current_team_id;
        $stats = [
            'active' => Campaign::where('team_id', $teamId)->whereIn('status', ['processing', 'sending'])->count(),
            'success_rate' => Campaign::where('team_id', $teamId)->where('sent_count', '>', 0)->select(DB::raw('AVG((del_count / sent_count) * 100)'))->value('AVG((del_count / sent_count) * 100)') ?? 100,
            'engagement' => Campaign::where('team_id', $teamId)->where('sent_count', '>', 0)->select(DB::raw('AVG((read_count / sent_count) * 100)'))->value('AVG((read_count / sent_count) * 100)') ?? 0,
            'total_sent' => Campaign::where('team_id', $teamId)->sum('sent_count'),
        ];

        return view('livewire.campaigns.campaign-list', [
            'campaigns' => $campaigns,
            'stats' => $stats
        ]);
    }
}
