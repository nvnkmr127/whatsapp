<?php

namespace App\Livewire\Campaigns;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Campaign;
use App\Models\CampaignDetail;
use App\Models\Message;

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
        $campaign = Campaign::where('team_id', \Illuminate\Support\Facades\Auth::user()->current_team_id)->findOrFail($this->campaignId);
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

        session([
            'retarget_ids' => $contactIds,
            'default_name' => "Follow-up: " . $campaign->name . " (" . str_replace('_', ' ', $this->retargetingCriteria) . ")"
        ]);

        return redirect()->route('campaigns.create');
    }

    public function mount($campaignId)
    {
        $this->campaignId = $campaignId;

        // Verify team ownership early
        $campaign = Campaign::findOrFail($campaignId);

        $user = \Illuminate\Support\Facades\Auth::user();
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

        // Source of truth for delivery metrics is Message status lifecycle.
        $messageQuery = Message::query()
            ->where('campaign_id', $campaign->id)
            ->where('direction', 'outbound');

        $messageCount = (clone $messageQuery)->count();
        $sent = (clone $messageQuery)->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $delivered = (clone $messageQuery)->whereIn('status', ['delivered', 'read'])->count();
        $read = (clone $messageQuery)->where('status', 'read')->count();
        $failed = (clone $messageQuery)->where('status', 'failed')->count();

        // Keep target size from campaign_details when available; fall back safely.
        $targeted = CampaignDetail::where('campaign_id', $campaign->id)->count();
        $total = max($targeted, $messageCount, (int) ($campaign->total_contacts ?? 0));

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
        $messages = Message::query()
            ->where('campaign_id', $this->campaignId)
            ->where('direction', 'outbound')
            ->with(['contact:id,name,phone_number'])
            ->latest()
            ->paginate(20);

        return view('livewire.campaigns.show', [
            'messages' => $messages
        ]);
    }
}
