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
    public $fromDate = '';
    public $toDate = '';
    public $perPage = 15;
    public $selectedPayload = null;
    public $showDetailsModal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFromDate()
    {
        $this->resetPage();
    }

    public function updatingToDate()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function viewDetails($id)
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        if (!$team && !$user->isSuperAdmin()) {
            return;
        }

        $query = WebhookPayload::query();

        if ($team && !$user->isSuperAdmin()) {
            $query->where('waba_id', $team->whatsapp_business_account_id);
        }

        $this->selectedPayload = $query->find($id);
        $this->showDetailsModal = true;
    }

    public function closeDetails()
    {
        $this->showDetailsModal = false;
        $this->selectedPayload = null;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->perPage = 15;
        $this->resetPage();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        if (!$team && !$user->isSuperAdmin()) {
            return view('livewire.webhooks.webhook-logs', [
                'logs' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15)
            ]);
        }

        $query = WebhookPayload::query()->latest();

        if ($team && !$user->isSuperAdmin()) {
            $query->where('waba_id', $team->whatsapp_business_account_id);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('payload', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return view('livewire.webhooks.webhook-logs', [
            'logs' => $query->paginate((int) $this->perPage)
        ]);
    }
}
