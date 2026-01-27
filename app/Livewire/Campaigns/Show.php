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
    public $showRetargetModal = false;
    public $retargetingCriteria = 'not_read'; // not_delivered, not_read, read, failed

    public function openRetargetModal()
    {
        $this->showRetargetModal = true;
    }

    public function retarget()
    {
        $campaign = Campaign::where('team_id', auth()->user()->current_team_id)->findOrFail($this->campaignId);
        // Use CampaignDetail to target ALL contacts, including those who never got a message (failed/pending)
        $query = \App\Models\CampaignDetail::where('campaign_id', $this->campaignId);

        switch ($this->retargetingCriteria) {
            case 'not_delivered':
                // "Didn't Receive" -> Failed, Pending, Sent (but no delivery receipt)
                $query->whereIn('status', ['failed', 'pending', 'sent']);
                break;
            case 'not_read':
                // "Didn't Read" -> Delivered but not read
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

    public function mount($campaignId)
    {
        $this->campaignId = $campaignId;

        // Verify team ownership early
        $campaign = Campaign::findOrFail($campaignId);

        $user = auth()->user();
        if ($user->is_super_admin) {
            return; // Super admin bypass
        }

        if ($campaign->team_id != $user->current_team_id) {
            abort(403, 'You do not have permission to access this campaign.');
        }
    }

    #[Computed]
    public function campaign()
    {
        return Campaign::findOrFail($this->campaignId);
    }

    #[Computed]
    public function campaignStats()
    {
        $campaign = $this->campaign;

        // Use CampaignDetail for stats to be accurate across all states
        // Note: Campaign model has cached counts (sent_count, etc) but let's be consistent or use them if accurate.
        // For 'Total', CampaignDetail count is better than 'total_contacts' sometimes if calc was wrong.
        // But let's trust the model cached counts for performance, 
        // OR calculate fresh from Details since this is a "Report" page and accuracy matters more than 10ms.

        $total = \App\Models\CampaignDetail::where('campaign_id', $campaign->id)->count();
        $sent = \App\Models\CampaignDetail::where('campaign_id', $campaign->id)->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $delivered = \App\Models\CampaignDetail::where('campaign_id', $campaign->id)->whereIn('status', ['delivered', 'read'])->count();
        $read = \App\Models\CampaignDetail::where('campaign_id', $campaign->id)->where('status', 'read')->count();
        $failed = \App\Models\CampaignDetail::where('campaign_id', $campaign->id)->where('status', 'failed')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'failed' => $failed,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100) : 0,
            'read_rate' => $delivered > 0 ? round(($read / $delivered) * 100) : 0,
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        // Use CampaignDetail instead of messages relation
        $messages = \App\Models\CampaignDetail::where('campaign_id', $this->campaignId)
            ->with(['contact']) // Eager load contact
            ->latest()
            ->paginate(20);

        return view('livewire.campaigns.show', [
            'messages' => $messages
        ]);
    }
}
