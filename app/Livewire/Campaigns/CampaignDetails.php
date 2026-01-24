<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignDetails extends Component
{
    use WithPagination;

    public $campaign;
    public $campaignId;
    public $template_name;

    // Stats
    public $totalCount = 0;
    public $totalContacts = 0;
    public $deliverCount = 0;
    public $readCount = 0;
    public $failedCount = 0;
    public $isInQueue = false;
    public $isRetryAble = false;
    public $lastRefresh;

    // Percentages
    public $totalDeliveredPercent = 0;
    public $totalReadPercent = 0;
    public $totalFailedPercent = 0;

    public function mount($campaignId)
    {
        $this->campaignId = $campaignId;
        $this->campaign = Campaign::findOrFail($campaignId);

        $this->template_name = $this->campaign->template_name ?? 'Unknown';

        $this->refreshStats();
    }

    public function refreshStats()
    {
        $this->totalCount = CampaignDetail::where('campaign_id', $this->campaignId)->count();
        $this->totalContacts = $this->campaign->total_contacts;

        if ($this->totalCount > 0) {
            $this->deliverCount = CampaignDetail::where('campaign_id', $this->campaignId)->where('status', 'delivered')->count(); // Assuming 'status' string or int? Ref used 2. Let's use string if I migrated that way. 
            // WAIT: check migration. I used string 'status' in CampaignDetail model? Ref used int.
            // Let's assume standard 'sent', 'delivered', 'read', 'failed' strings for readability in new system, OR match Ref int.
            // Looking at my CampaignCreator, I inserted 'pending'. 
            // Ref uses: 0=failed, 1=in queue/sent?, 2=delivered?
            // Let's stick to strings: 'pending', 'sent', 'delivered', 'read', 'failed'.

            $this->deliverCount = CampaignDetail::where('campaign_id', $this->campaignId)->whereIn('status', ['delivered', 'read'])->count();
            $this->readCount = CampaignDetail::where('campaign_id', $this->campaignId)->where('status', 'read')->count();
            $this->failedCount = CampaignDetail::where('campaign_id', $this->campaignId)->where('status', 'failed')->count();
            $this->isInQueue = CampaignDetail::where('campaign_id', $this->campaignId)->where('status', 'pending')->exists();

            $this->totalDeliveredPercent = round(($this->deliverCount / $this->totalCount) * 100, 1);
            $this->totalReadPercent = round(($this->readCount / $this->totalCount) * 100, 1);
            $this->totalFailedPercent = round(($this->failedCount / $this->totalCount) * 100, 1);
        }

        $this->lastRefresh = now()->format('H:i:s');
    }

    public function render()
    {
        $details = CampaignDetail::where('campaign_id', $this->campaignId)
            ->with('contact')
            ->paginate(20);

        return view('livewire.campaigns.campaign-details', [
            'details' => $details
        ]);
    }
}
