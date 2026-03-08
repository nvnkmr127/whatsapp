<?php

namespace App\Livewire\Crm;

use App\Services\OnboardingService;
use Livewire\Component;
use Livewire\WithPagination;

class OnboardingManager extends Component
{
    use WithPagination;

    public $filter = 'not_started'; // not_started, abandoned, no_activity, active

    protected $queryString = ['filter'];

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        $service = new OnboardingService();

        $teams = match($this->filter) {
            'not_started' => $service->getSignupsNotStarted(),
            'abandoned' => $service->getAbandonedSetup(),
            'no_activity' => $service->getSetupCompletedNoActivity(),
            'active' => \App\Models\Team::whereNotNull('last_webhook_received_at')->orderByDesc('last_webhook_received_at'),
            default => $service->getSignupsNotStarted(),
        };

        return view('livewire.crm.onboarding-manager', [
            'teams' => $teams->paginate(10),
            'counts' => [
                'not_started' => $service->getSignupsNotStarted()->count(),
                'abandoned' => $service->getAbandonedSetup()->count(),
                'no_activity' => $service->getSetupCompletedNoActivity()->count(),
                'active' => \App\Models\Team::whereNotNull('last_webhook_received_at')->count(),
            ],
        ]);
    }
}
