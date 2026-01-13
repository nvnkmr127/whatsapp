<?php

namespace App\Livewire\Admin\Campaign;

use App\Models\Campaign;
use Livewire\Component;

class CampaignList extends Component
{
    public $campaign_id = null;

    public $confirmingDeletion = false;

    protected $listeners = [
        'confirmDelete' => 'confirmDelete',
    ];

    public function mount()
    {
        if (! checkPermission('campaigns.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }
    }

    public function confirmDelete($campaignId)
    {
        $this->campaign_id        = $campaignId;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if (checkPermission('campaigns.delete')) {
            Campaign::findOrFail($this->campaign_id)->delete();
            $this->confirmingDeletion = false;
            $this->notify(['type' => 'success', 'message' => t('campaign_delete_successfully')]);
            $this->dispatch('pg:eventRefresh-campaign-table-r3hjpl-table');
        }
    }

    public function refreshTable()
    {
        $this->dispatch('pg:eventRefresh-campaign-table-r3hjpl-table');
    }

    public function render()
    {
        return view('livewire.admin.campaign.campaign-list');
    }
}
