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
    public $detailedStats;
    public $plans;
    public $showTopUpModal = false;
    public $showChangePlanModal = false;
    public $selectedPlan = null;
    public $planImpact = null;
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
        $this->plans = Plan::all();

        // Get wallet
        $this->wallet = TeamWallet::firstOrCreate(
            ['team_id' => $this->team->id],
            ['balance' => 0]
        );

        // Get detailed usage stats
        $billingService = app(BillingService::class);
        $this->detailedStats = $billingService->getDetailedUsageStats($this->team);

        // Backward compatibility for existing view variable
        $this->usage = $this->detailedStats['messages']['usage'];
        $this->usagePercentage = ($this->detailedStats['messages']['limit'] > 0)
            ? ($this->usage / $this->detailedStats['messages']['limit']) * 100
            : 0;
    }

    public function selectPlan($planName)
    {
        $this->selectedPlan = $planName;
        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $this->planImpact = $subscriptionService->analyzeImpact($this->team, $planName);
        $this->showChangePlanModal = true;
    }

    public function confirmPlanChange()
    {
        if (!$this->selectedPlan)
            return;

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $subscriptionService->changePlan($this->team, $this->selectedPlan);

        session()->flash('message', "Plan successfully changed to " . ucfirst($this->selectedPlan));
        $this->showChangePlanModal = false;
        $this->loadData();
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
