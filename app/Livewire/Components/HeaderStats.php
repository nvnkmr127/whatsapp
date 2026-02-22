<?php

namespace App\Livewire\Components;

use App\Services\BillingService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class HeaderStats extends Component
{
    public $stats = [];
    public $showModal = false;

    public function mount()
    {
        // Load initially to prevent empty state if needed, 
        // but fetching on open is better for performance if heavy.
        // However, BillingService is fast (cached).
        if (Auth::check() && Auth::user()->currentTeam) {
            $this->loadStats();
        }
    }

    public function loadStats()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $billingService = app(BillingService::class);
            $this->stats = $billingService->getDetailedUsageStats(Auth::user()->currentTeam);
        }
    }

    public function openModal()
    {
        $this->loadStats();
        $this->showModal = true;
    }

    public function render()
    {
        return view('livewire.components.header-stats');
    }
}
