<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
use App\Models\TeamWallet;
use App\Models\TeamTransaction;
use App\Services\BillingService;
use Livewire\Component;
use Livewire\WithPagination;

class BillingDashboard extends Component
{
    use WithPagination;

    public $team;
    public $plan;
    public $wallet;
    public $usage;
    public $usagePercentage;
    public $showTopUpModal = false;
    public $topUpAmount = 50;

    public function mount()
    {
        $this->team = auth()->user()->currentTeam;
        $this->loadData();
    }

    public function loadData()
    {
        // Get current plan
        $planName = $this->team->subscription_plan ?? 'basic';
        $this->plan = Plan::where('name', $planName)->first();

        // Get wallet
        $this->wallet = TeamWallet::firstOrCreate(
            ['team_id' => $this->team->id],
            ['balance' => 0]
        );

        // Get usage stats
        $billingService = new BillingService();
        $this->usagePercentage = $billingService->getUsagePercentage($this->team);

        // Calculate actual usage
        $this->usage = \App\Models\Message::where('team_id', $this->team->id)
            ->where('direction', 'outbound')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function openTopUpModal()
    {
        $this->showTopUpModal = true;
    }

    public function closeTopUpModal()
    {
        $this->showTopUpModal = false;
        $this->topUpAmount = 50;
    }

    public function topUp()
    {
        $this->validate([
            'topUpAmount' => 'required|numeric|min:10|max:10000'
        ]);

        // For now, just add credits directly (Phase 4 will add payment gateway)
        $billingService = new BillingService();
        $billingService->deposit($this->team, $this->topUpAmount, 'Manual top-up');

        session()->flash('message', "Successfully added $" . number_format($this->topUpAmount, 2) . " to your wallet!");

        $this->closeTopUpModal();
        $this->loadData();
    }

    public function render()
    {
        $transactions = TeamTransaction::where('team_id', $this->team->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.billing.billing-dashboard', [
            'transactions' => $transactions
        ]);
    }
}
