<?php

namespace App\Livewire\Calls;

use App\Services\BillingService;
use App\Services\CallService;
use Livewire\Component;

class CallAnalytics extends Component
{
    public $period = 'month'; // today, week, month, year
    public $dateRange = [];

    public function mount()
    {
        $this->setDateRange();
    }

    public function updatedPeriod()
    {
        $this->setDateRange();
    }

    protected function setDateRange()
    {
        switch ($this->period) {
            case 'today':
                $this->dateRange = [now()->startOfDay(), now()->endOfDay()];
                break;
            case 'week':
                $this->dateRange = [now()->startOfWeek(), now()->endOfWeek()];
                break;
            case 'month':
                $this->dateRange = [now()->startOfMonth(), now()->endOfMonth()];
                break;
            case 'year':
                $this->dateRange = [now()->startOfYear(), now()->endOfYear()];
                break;
        }
    }

    public function render()
    {
        $team = auth()->user()->currentTeam;
        $callService = new CallService($team);
        $billingService = new BillingService();

        // Get call statistics
        $statistics = $callService->getCallStatistics($this->period);

        // Get billing stats
        $billingStats = $billingService->getCallUsageStats($team, $this->dateRange[0], $this->dateRange[1]);

        // Get usage limits
        $usageLimits = $billingService->checkCallLimits($team);

        // Get cost breakdown (last 30 days)
        $costBreakdown = $billingService->getCallCostBreakdown($team, 30);

        // Get top contacts by call volume
        $topContacts = \App\Models\WhatsAppCall::where('team_id', $team->id)
            ->whereBetween('created_at', $this->dateRange)
            ->with('contact:id,name,phone_number')
            ->get()
            ->groupBy('contact_id')
            ->map(function ($calls) {
                return [
                    'contact' => $calls->first()->contact,
                    'total_calls' => $calls->count(),
                    'total_duration' => $calls->sum('duration_seconds'),
                    'total_cost' => $calls->sum('cost_amount'),
                ];
            })
            ->sortByDesc('total_calls')
            ->take(10)
            ->values();

        return view('livewire.calls.call-analytics', [
            'statistics' => $statistics,
            'billingStats' => $billingStats,
            'usageLimits' => $usageLimits,
            'costBreakdown' => $costBreakdown,
            'topContacts' => $topContacts,
        ]);
    }
}
