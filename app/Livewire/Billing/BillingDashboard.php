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
    public $isTrial = false;
    public $trialEndsAt = null;
    public $showTopUpModal = false;
    public $showChangePlanModal = false;
    public $selectedPlan = null;
    public $planImpact = null;
    public $topUpAmount = 50;

    public function mount()
    {
        $this->team = auth()->user()->currentTeam;
        if ($this->team) {
            $this->loadData();
        }
    }

    public function loadData()
    {
        // Get current plan
        $planName = $this->team->subscription_plan ?? 'basic';
        $this->plan = Plan::where('name', $planName)->first();
        
        // Fallback if plan doesn't exist (e.g. legacy plan or bad data)
        if (!$this->plan) {
            $this->plan = Plan::where('name', 'basic')->first();
            // Optional: Log warning or auto-correct team plan
            if ($this->plan) {
                // Temporary fix: Show basic plan details even if team has invalid plan string
            }
        }
        
        $this->plans = Plan::all();

        // Get wallet
        $this->wallet = TeamWallet::firstOrCreate(
            ['team_id' => $this->team->id],
            ['balance' => 0]
        );

        // Get detailed usage stats
        $billingService = app(BillingService::class);
        $this->detailedStats = $billingService->getDetailedUsageStats($this->team);

        // Check trial status
        $this->isTrial = $this->team->subscription_status === 'trial';
        $this->trialEndsAt = $this->team->trial_ends_at;

        // Backward compatibility for existing view variable
        // Ensure keys exist in detailedStats to prevent errors
        $msgStats = $this->detailedStats['messages'] ?? ['usage' => 0, 'limit' => 0];
        $this->usage = $msgStats['usage'];
        $this->usagePercentage = ($msgStats['limit'] > 0)
            ? ($this->usage / $msgStats['limit']) * 100
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
        if (!$this->team) {
            return view('livewire.billing.billing-dashboard', [
                'transactions' => collect()
            ]);
        }

        $transactions = TeamTransaction::where('team_id', $this->team->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.billing.billing-dashboard', [
            'transactions' => $transactions
        ]);
    }
}
