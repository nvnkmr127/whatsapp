<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public $campaignId;

    public $statusLine = '';

    public function mount($campaign)
    {
        $this->campaignId = $campaign; // Handle both ID and Model if needed, but route pass ID
        if ($campaign instanceof Campaign) {
            $this->campaignId = $campaign->id;
        }
    }

    #[Computed]
    public function campaign()
    {
        return Campaign::findOrFail($this->campaignId);
    }

    #[Computed]
    public function metrics()
    {
        $campaign = $this->campaign;

        // Count unique sent recipients from messages table to avoid inflation from retries
        $agg = $campaign->messages()
            ->selectRaw("COUNT(DISTINCT CASE WHEN status IN ('sent','delivered','read') THEN contact_id END) AS unique_sent,
                COUNT(DISTINCT CASE WHEN status = 'delivered' THEN contact_id END) AS unique_del,
                COUNT(DISTINCT CASE WHEN status = 'read' THEN contact_id END) AS unique_read,
                COUNT(DISTINCT CASE WHEN status = 'failed' THEN contact_id END) AS unique_failed")
            ->first();

        $totalContacts = max(1, (int) $campaign->total_contacts);
        $uniqueSent = (int) ($agg->unique_sent ?? 0);
        $sentCount = $uniqueSent > 0 ? $uniqueSent : min($totalContacts, (int) $campaign->sent_count);

        return [
            'sent' => min($totalContacts, $sentCount),
            'delivered' => max((int) $campaign->del_count, (int) ($agg->unique_del ?? 0)),
            'read' => max((int) $campaign->read_count, (int) ($agg->unique_read ?? 0)),
            'failed' => (int) ($agg->unique_failed ?? 0),
            'total' => $totalContacts,
        ];
    }

    #[Computed]
    public function recentMessages()
    {
        return \App\Models\Message::where('campaign_id', $this->campaignId)
            ->with(['contact:id,name,phone_number'])
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function speed()
    {
        $campaign = $this->campaign;
        if (! $campaign->started_at || $campaign->status === 'completed') {
            return 0;
        }

        // Use last 5 minutes from messages table for more accurate "current" speed
        $sentInLast5Min = \App\Models\Message::where('campaign_id', $campaign->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereIn('status', ['sent', 'delivered', 'read'])
            ->count();

        return round($sentInLast5Min / 5, 1); // msgs per minute
    }

    public function getListeners()
    {
        return [
            "echo-private:campaign.{$this->campaignId}.progress,progress.updated" => 'onProgressUpdate',
        ];
    }

    public function onProgressUpdate($data)
    {
        $this->dispatch('$refresh');
    }

    public function replayAllFailed()
    {
        try {
            $campaign = Campaign::findOrFail($this->campaignId);
            $failedMessages = \App\Models\Message::where('campaign_id', $this->campaignId)
                ->where('status', 'failed')
                ->get();

            if ($failedMessages->isEmpty()) {
                $this->dispatch('notify', message: 'No failed messages found to replay.', type: 'info');
                return;
            }

            // Clear any lingering circuit breaker errors for this team
            \Illuminate\Support\Facades\Cache::forget("whatsapp_consecutive_errors:{$campaign->team_id}");
            if ($campaign->team && $campaign->team->whatsapp_setup_state === \App\Enums\IntegrationState::RESTRICTED) {
                $campaign->team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::READY]);
            }

            foreach ($failedMessages as $message) {
                // Reset message status
                $message->update([
                    'status' => 'queued',
                    'error_message' => null,
                    'last_error' => null,
                    'retry_count' => 0,
                    'next_retry_at' => null,
                ]);

                // Reset CampaignDetail status
                \App\Models\CampaignDetail::where('campaign_id', $this->campaignId)
                    ->where('contact_id', $message->contact_id)
                    ->update([
                        'status' => 'pending',
                    ]);

                // Re-dispatch the job
                \App\Jobs\SendCampaignMessageJob::dispatch($this->campaignId, $message->contact_id);
            }

            $this->dispatch('notify', message: count($failedMessages) . ' failed message(s) re-queued for delivery.', type: 'success');
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Manual Campaign Replay All Error: ' . $e->getMessage());
            $this->dispatch('notify', message: 'Replay failed: ' . $e->getMessage(), type: 'error');
        }
    }

    public function resumeCampaign()
    {
        try {
            $campaign = Campaign::findOrFail($this->campaignId);
            $campaign->update(['status' => 'processing']);

            // Clear any lingering circuit breaker errors for this team
            \Illuminate\Support\Facades\Cache::forget("whatsapp_consecutive_errors:{$campaign->team_id}");
            if ($campaign->team && $campaign->team->whatsapp_setup_state === \App\Enums\IntegrationState::RESTRICTED) {
                $campaign->team->update(['whatsapp_setup_state' => \App\Enums\IntegrationState::READY]);
            }

            $this->dispatch('notify', message: 'Campaign resumed! Message sending has continued.', type: 'success');
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Could not resume campaign: ' . $e->getMessage(), type: 'error');
        }
    }

    public function pauseCampaign()
    {
        try {
            $campaign = Campaign::findOrFail($this->campaignId);
            $campaign->update(['status' => 'paused']);
            $this->dispatch('notify', message: 'Campaign paused.', type: 'info');
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Could not pause campaign: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.campaigns.dashboard');
    }
}
