<?php

namespace App\Livewire\Webhooks;

use App\Models\WebhookPayload;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class WebhookLogs extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $selectedPayload = null;
    public $showDetailsModal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewDetails($id)
    {
        $this->selectedPayload = WebhookPayload::where('waba_id', auth()->user()->currentTeam->whatsapp_business_account_id)->find($id);
        $this->showDetailsModal = true;
    }

    public function closeDetails()
    {
        $this->showDetailsModal = false;
        $this->selectedPayload = null;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = WebhookPayload::where('waba_id', auth()->user()->currentTeam->whatsapp_business_account_id)
            ->latest();

        if ($this->search) {
            $query->where('payload', 'like', '%' . $this->search . '%')
                ->orWhere('id', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.webhooks.webhook-logs', [
            'logs' => $query->paginate(15)
        ]);
    }
}
