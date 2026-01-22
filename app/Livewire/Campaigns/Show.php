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
        return Campaign::with('messages')->findOrFail($this->campaignId);
    }

    #[Computed]
    public function campaignStats()
    {
        $campaign = $this->campaign;

        $sentCount = $campaign->sent_count;
        $deliveredCount = $campaign->del_count;
        $readCount = $campaign->read_count;
        $failedCount = $campaign->messages()->where('status', 'failed')->count();

        return [
            'total' => $campaign->total_contacts,
            'sent' => $sentCount,
            'delivered' => $deliveredCount,
            'read' => $readCount,
            'failed' => $failedCount,
            'delivery_rate' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100) : 0,
            'read_rate' => $deliveredCount > 0 ? round(($readCount / $deliveredCount) * 100) : 0,
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $messages = $this->campaign->messages()->latest()->paginate(20);

        return view('livewire.campaigns.show', [
            'messages' => $messages
        ]);
    }
}
