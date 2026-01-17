<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
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

        return view('livewire.campaigns.campaign-list', [
            'campaigns' => $campaigns
        ]);
    }
}
