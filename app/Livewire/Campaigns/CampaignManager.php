<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\ContactTag;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\BroadcastService;

class CampaignManager extends Component
{
    use WithPagination;

    public $showCreateModal = false;

    // Form Config
    public $name;
    public $templateName;
    public $selectedTags = []; // IDs
    public $sendNow = true;
    public $scheduledAt;

    // Loaded Data
    public $availableTemplates = [];
    public $availableTags = [];

    public function mount()
    {
        $this->availableTags = ContactTag::where('team_id', auth()->user()->currentTeam->id)->get();
        // Templates should come from DB or API
        $this->availableTemplates = \App\Models\WhatsAppTemplate::where('team_id', auth()->user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get();
    }

    public function create()
    {
        $this->validate([
            'name' => 'required',
            'templateName' => 'required',
        ]);

        $segmentConfig = [
            'tags' => $this->selectedTags
        ];

        $campaign = Campaign::create([
            'team_id' => auth()->user()->currentTeam->id,
            'name' => $this->name,
            'template_name' => $this->templateName,
            'segment_config' => $segmentConfig,
            'status' => 'draft',
            'scheduled_at' => $this->sendNow ? now() : $this->scheduledAt,
        ]);

        if ($this->sendNow) {
            (new BroadcastService())->launch($campaign);
            audit('campaign.launched', "Campaign '{$campaign->name}' launched immediately.", $campaign);
            session()->flash('message', 'Campaign launched successfully!');
        } else {
            $campaign->update(['status' => 'scheduled']);
            audit('campaign.scheduled', "Campaign '{$campaign->name}' scheduled for {$campaign->scheduled_at}.", $campaign);
            session()->flash('message', 'Campaign scheduled!');
        }

        $this->showCreateModal = false;
        $this->reset(['name', 'templateName', 'selectedTags']);
    }

    public $retargetingCampaignId;
    public $retargetingCriteria = 'not_read'; // not_delivered, not_read, read, failed

    public function openRetargetModal($campaignId)
    {
        $this->retargetingCampaignId = $campaignId;
        $this->dispatch('open-retarget-modal');
    }

    public function retarget()
    {
        $campaign = Campaign::where('team_id', auth()->user()->currentTeam->id)->findOrFail($this->retargetingCampaignId);

        $query = $campaign->messages();

        switch ($this->retargetingCriteria) {
            case 'not_delivered':
                $query->where('status', '!=', 'delivered');
                break;
            case 'not_read':
                // Delivered but not read
                $query->where('status', 'delivered')->whereNull('read_at');
                break;
            case 'read':
                $query->whereNotNull('read_at');
                break;
            case 'failed':
                $query->where('status', 'failed');
                break;
        }

        $contactIds = $query->pluck('contact_id')->toArray();

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

    public function render()
    {
        $campaigns = Campaign::where('team_id', auth()->user()->currentTeam->id)->latest()->paginate(10);
        return view('livewire.campaigns.campaign-manager', ['campaigns' => $campaigns]);
    }
}
