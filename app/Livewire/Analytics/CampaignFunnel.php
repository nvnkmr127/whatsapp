<?php

namespace App\Livewire\Analytics;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\Order;
use App\Models\CustomerEvent;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CampaignFunnel extends Component
{
    public $campaignId;
    public $funnelData = [];
    public $lastRefresh;

    public function mount($campaignId = null)
    {
        $this->campaignId = $campaignId;
        $this->loadFunnelData();
    }

    public function loadFunnelData()
    {
        $teamId = auth()->user()->currentTeam->id;

        // 1. Broadcast Sent (Outreach)
        $sentRaw = Message::where('team_id', $teamId)
            ->when($this->campaignId, fn($q) => $q->where('campaign_id', $this->campaignId))
            ->where('direction', 'outbound')
            ->whereNotNull('campaign_id')
            ->count();

        // 2. Delivered
        $deliveredRaw = Message::where('team_id', $teamId)
            ->when($this->campaignId, fn($q) => $q->where('campaign_id', $this->campaignId))
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        // 3. Read
        $readRaw = Message::where('team_id', $teamId)
            ->when($this->campaignId, fn($q) => $q->where('campaign_id', $this->campaignId))
            ->where('status', 'read')
            ->count();

        // 4. Customer Reply (Engagement)
        // Attribute inbound messages received within 24h of a campaign message
        $repliesRaw = Message::where('team_id', $teamId)
            ->where('direction', 'inbound')
            ->whereNotNull('attributed_campaign_id')
            ->when($this->campaignId, fn($q) => $q->where('attributed_campaign_id', $this->campaignId))
            ->count();

        // 5. Automation Triggered
        // Events linked to campaign attribution
        $automationRaw = CustomerEvent::where('team_id', $teamId)
            ->where('event_type', 'flow_started')
            ->where(function ($query) {
                if ($this->campaignId) {
                    $query->where('event_data->attributed_campaign_id', $this->campaignId);
                }
            })
            ->count();

        // 6. Agent Response (Assisted Conversion)
        // Outbound messages that are NOT part of a campaign but have attribution
        $agentRaw = Message::where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->whereNull('campaign_id')
            ->whereNotNull('attributed_campaign_id')
            ->when($this->campaignId, fn($q) => $q->where('attributed_campaign_id', $this->campaignId))
            ->count();

        // 7. Order Created (Conversion)
        // Orders from contacts who interacted with the campaign
        $orderRaw = Order::where('team_id', $teamId)
            ->whereIn('contact_id', function ($query) {
                $query->select('contact_id')
                    ->from('messages')
                    ->whereNotNull('campaign_id')
                    ->when($this->campaignId, fn($q) => $q->where('campaign_id', $this->campaignId));
            })
            ->count();

        $this->funnelData = [
            'stages' => [
                ['label' => 'Sent', 'value' => $sentRaw, 'color' => 'slate-400', 'icon' => 'paper-airplane'],
                ['label' => 'Delivered', 'value' => $deliveredRaw, 'color' => 'blue-400', 'icon' => 'check'],
                ['label' => 'Read', 'value' => $readRaw, 'color' => 'wa-teal', 'icon' => 'eye'],
                ['label' => 'Replied', 'value' => $repliesRaw, 'color' => 'purple-400', 'icon' => 'chat-bubble-left'],
                ['label' => 'Automated', 'value' => $automationRaw, 'color' => 'indigo-400', 'icon' => 'bolt'],
                ['label' => 'Agent Chat', 'value' => $agentRaw, 'color' => 'orange-400', 'icon' => 'user-group'],
                ['label' => 'Orders', 'value' => $orderRaw, 'color' => 'emerald-400', 'icon' => 'shopping-cart'],
            ]
        ];

        $this->lastRefresh = now()->format('H:i:s');
    }

    public function render()
    {
        return view('livewire.analytics.campaign-funnel');
    }
}
