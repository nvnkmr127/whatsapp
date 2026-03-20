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
        $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->current_team_id)->find($this->campaignIdToDelete);
        if ($campaign) {
            $campaign->delete();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Campaign deleted successfully.'
            ]);
        }
        $this->confirmingDeletion = false;
    }

    public function cloneCampaign($id)
    {
        \Illuminate\Support\Facades\Gate::authorize('manage-campaigns');
        $campaign = Campaign::where('team_id', auth()->user()->current_team_id)->findOrFail($id);

        try {
            DB::beginTransaction();

            $clone = $campaign->replicateAndReset();
            $clone->save();

            DB::commit();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Campaign cloned successfully. You can now edit and send it.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to clone campaign: ' . $e->getMessage()
            ]);
        }
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

    public function pause($id)
    {
        $campaign = Campaign::where('team_id', auth()->user()->current_team_id)->findOrFail($id);
        if (in_array($campaign->status, ['processing', 'sending', 'queued'])) {
            $campaign->update(['status' => 'paused']);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Campaign paused successfully.'
            ]);
        }
    }

    public function resume($id)
    {
        $campaign = Campaign::where('team_id', auth()->user()->current_team_id)->findOrFail($id);
        if ($campaign->status === 'paused') {
            $campaign->update(['status' => 'processing']);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Campaign resumed successfully.'
            ]);
        }
    }

    public function retarget()
    {
        $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->current_team_id)->findOrFail($this->selectedCampaignId);
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

        session([
            'retarget_ids' => $contactIds,
            'default_name' => "Retarget: " . $campaign->name . " (" . str_replace('_', ' ', $this->retargetingCriteria) . ")"
        ]);

        return redirect()->route('campaigns.create');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = Campaign::query();

        if ($this->search) {
            $query->where('campaign_name', 'like', '%' . $this->search . '%');
        }

        // Add team scope
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->current_team_id) {
            $query->where('team_id', \Illuminate\Support\Facades\Auth::user()->current_team_id);
        }

        $campaigns = $query->latest()->paginate(10);

        // Module-Level Core Metrics
        $teamId = \Illuminate\Support\Facades\Auth::user()->current_team_id;
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
