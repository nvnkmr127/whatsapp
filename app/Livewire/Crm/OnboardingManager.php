<?php

namespace App\Livewire\Crm;

use App\Services\OnboardingService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class OnboardingManager extends Component
{
    use WithPagination;

    public $filter = 'leads'; // leads, not_started, abandoned, no_activity, active

    protected $queryString = ['filter'];

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        $service = new OnboardingService();

        if ($this->filter === 'leads') {
            $allLeads = $service->getOtpLeads();
            
            // Manual Pagination for Collection
            $perPage = 10;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentItems = $allLeads->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $teams = new LengthAwarePaginator($currentItems, count($allLeads), $perPage);
            $teams->setPath(request()->url());
        } else {
            $query = match($this->filter) {
                'not_started' => $service->getSignupsNotStarted(),
                'abandoned' => $service->getAbandonedSetup(),
                'no_activity' => $service->getSetupCompletedNoActivity(),
                'active' => \App\Models\Team::whereNotNull('last_webhook_received_at')->orderByDesc('last_webhook_received_at'),
                default => $service->getSignupsNotStarted(),
            };
            $teams = $query->paginate(10);
        }

        return view('livewire.crm.onboarding-manager', [
            'teams' => $teams,
            'counts' => $service->getFunnelCounts(),
        ]);
    }
}
