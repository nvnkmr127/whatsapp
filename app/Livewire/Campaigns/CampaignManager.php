<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\ContactTag;
use App\Services\BroadcastService;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignManager extends Component
{
    use WithPagination;

    public $showCreateModal = false;

    // Form Config
    public $name;

    public $campaignType = 'template'; // 'template' or 'workflow'

    public $templateName;

    public $workflowId;

    public $selectedTags = []; // IDs

    public $sendNow = true;

    public $scheduledAt;

    // Loaded Data
    public $availableTemplates = [];

    public $availableTags = [];

    public $availableWorkflows = [];

    public function mount()
    {
        $this->availableTags = ContactTag::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->get();
        // Templates should come from DB or API
        $this->availableTemplates = \App\Models\WhatsappTemplate::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)
            ->where('status', 'APPROVED')
            ->get();

        $this->availableWorkflows = collect();
        if (class_exists(\App\Models\Workflow::class)) {
            $this->availableWorkflows = \App\Models\Workflow::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)
                ->where('is_active', true)
                ->get();
        }
    }

    public function create()
    {
        $this->validate([
            'name' => 'required',
            'campaignType' => 'required|in:template,workflow',
        ]);

        if ($this->campaignType === 'template') {
            $this->validate(['templateName' => 'required']);
        } else {
            $this->validate(['workflowId' => 'required']);
        }

        $segmentConfig = [
            'tags' => $this->selectedTags,
        ];

        $campaign = Campaign::create([
            'team_id' => \Illuminate\Support\Facades\Auth::user()->currentTeam->id,
            'name' => $this->name,
            'template_name' => $this->campaignType === 'template' ? $this->templateName : null,
            'segment_config' => $segmentConfig,
            'status' => 'draft',
            'scheduled_at' => $this->sendNow ? now() : $this->scheduledAt,
        ]);

        if ($this->campaignType === 'workflow') {
            // Save workflow data in campaign
            $campaign->update(['segment_config' => array_merge($segmentConfig, ['workflow_id' => $this->workflowId])]);
        }

        if ($this->sendNow) {
            array_push($this->selectedTags, ...session('retarget_ids', []));

            if ($this->campaignType === 'workflow') {
                $query = \App\Models\Contact::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id);
                if (! empty($this->selectedTags)) {
                    $query->whereHas('tags', function ($q) {
                        $q->whereIn('contact_tags.id', $this->selectedTags);
                    });
                }
                $contacts = $query->get();
                $workflow = \App\Models\Workflow::find($this->workflowId);

                if ($workflow && $contacts->count() > 0) {
                    app(\App\Services\WorkflowEngine::class)->triggerBatch($workflow, $contacts, ['source' => 'campaign']);
                }
                $campaign->update(['status' => 'completed', 'sent_count' => $contacts->count(), 'total_contacts' => $contacts->count()]);
                audit('campaign.workflow_launched', null, $campaign->id, 'campaign', ['name' => $campaign->name, 'workflow' => $workflow->name, 'count' => $contacts->count()]);
                session()->flash('message', 'Workflow sequence batch launched successfully!');
            } else {
                app(BroadcastService::class)->launch($campaign);
                audit('campaign.launched', null, $campaign->id, 'campaign', ['name' => $campaign->name]);
                session()->flash('message', 'Campaign launched successfully!');
            }
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
        $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->findOrFail($this->retargetingCampaignId);

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
                'message' => 'No contacts found matching criteria.',
            ]);

            return;
        }

        session([
            'retarget_ids' => $contactIds,
            'default_name' => 'Retarget: '.$campaign->name.' ('.str_replace('_', ' ', $this->retargetingCriteria).')',
        ]);

        return redirect()->route('campaigns.create');
    }

    public function render()
    {
        $campaigns = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)->latest()->paginate(10);

        return view('livewire.campaigns.campaign-manager', ['campaigns' => $campaigns]);
    }
}
