<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
use App\Models\TeamInvoice;
use App\Models\TeamTransaction;
use App\Models\TeamWallet;
use App\Services\BillingService;
use Livewire\Attributes\Computed;
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

    public $searchTransaction = '';

    public $transactionType = '';

    public $showExtensionModal = false;

    public $extensionDays = 14;

    public $extensionReason = '';

    public $isProcessingPayment = false;

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
        if (! $this->plan) {
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
        if (! $this->selectedPlan) {
            return;
        }

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $subscriptionService->changePlan($this->team, $this->selectedPlan);

        session()->flash('message', 'Plan successfully changed to '.ucfirst($this->selectedPlan));
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
            'topUpAmount' => 'required|numeric|min:10|max:10000',
        ]);

        $this->isProcessingPayment = true;

        // In production, this would call a real gateway like Stripe
        // For now, we simulate a 1.5s delay to mimic real-world processing
        $this->dispatch('simulating-payment');

        // We use Livewire's background-like delay simulation
        sleep(2); 

        $billingService = new BillingService;
        $billingService->deposit($this->team, $this->topUpAmount, 'Gateway Deposit (Simulated)');

        session()->flash('message', 'Secure payment successful! $'.number_format($this->topUpAmount, 2).' added.');

        $this->isProcessingPayment = false;
        $this->closeTopUpModal();
        $this->loadData();
    }

    #[Computed]
    public function invoices()
    {
        if (! $this->team) {
            return collect();
        }

        return TeamInvoice::where('team_id', $this->team->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function downloadInvoice($invoiceId)
    {
        $this->authorize('view', $this->team);

        $invoice = TeamInvoice::where('team_id', $this->team->id)->findOrFail($invoiceId);

        return redirect()->route('invoices.download', $invoice->id);
    }

    public function openExtensionModal()
    {
        $this->showExtensionModal = true;
    }

    public function closeExtensionModal()
    {
        $this->showExtensionModal = false;
        $this->extensionDays = 14;
        $this->extensionReason = '';
    }

    public function submitExtensionRequest()
    {
        $this->validate([
            'extensionDays' => 'required|integer|min:7|max:30',
            'extensionReason' => 'required|string|min:10|max:500',
        ]);

        \App\Models\TrialExtensionRequest::create([
            'team_id' => $this->team->id,
            'user_id' => auth()->id(),
            'days' => $this->extensionDays,
            'reason' => $this->extensionReason,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Trial extension request submitted successfully. Our team will review it shortly.');
        $this->closeExtensionModal();
    }

    #[Computed]
    public function upcomingBillingDate()
    {
        if (! $this->team) {
            return null;
        }

        // If on trial, next billing is trial end date (if they convert)
        // If active, it's subscription_ends_at
        return $this->team->subscription_ends_at ?? $this->team->trial_ends_at;
    }

    #[Computed]
    public function hasPendingExtensionRequest()
    {
        if (! $this->team) {
            return false;
        }

        return \App\Models\TrialExtensionRequest::where('team_id', $this->team->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function render()
    {
        if (! $this->team) {
            return view('livewire.billing.billing-dashboard', [
                'transactions' => collect(),
            ]);
        }

        $query = TeamTransaction::where('team_id', $this->team->id)
            ->orderBy('created_at', 'desc');

        if (! empty($this->searchTransaction)) {
            $query->where('description', 'like', '%'.$this->searchTransaction.'%');
        }

        if (! empty($this->transactionType)) {
            $query->where('type', $this->transactionType);
        }

        $transactions = $query->paginate(10);

        return view('livewire.billing.billing-dashboard', [
            'transactions' => $transactions,
        ]);
    }
}
