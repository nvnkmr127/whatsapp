<?php

namespace App\Livewire\Calls;

use App\Services\BillingService;
use App\Services\CallService;
use App\Models\Contact;
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

        if (!$team) {
            return view('livewire.calls.call-analytics', [
                'statistics' => [],
                'billingStats' => null,
                'usageLimits' => null,
                'costBreakdown' => [],
                'topContacts' => collect(),
            ]);
        }

        $callService = app(CallService::class)->setTeam($team);
        $billingService = new BillingService();

        // Get call statistics
        $statistics = $callService->getCallStatistics($this->period);

        // Get billing stats
        $billingStats = $billingService->getCallUsageStats($team, $this->dateRange[0], $this->dateRange[1]);

        // Get usage limits
        $usageLimits = $billingService->checkCallLimits($team);

        // Get cost breakdown (last 30 days)
        $costBreakdown = $billingService->getCallCostBreakdown($team, 30);

        // Get top contacts by call volume (aggregate in SQL for performance)
        $topContactsRaw = \App\Models\WhatsAppCall::selectRaw(
            'contact_id, COUNT(*) as total_calls, SUM(duration_seconds) as total_duration, SUM(cost_amount) as total_cost'
        )
            ->where('team_id', $team->id)
            ->whereBetween('created_at', $this->dateRange)
            ->groupBy('contact_id')
            ->orderByDesc('total_calls')
            ->limit(10)
            ->get();

        $contactIds = $topContactsRaw->pluck('contact_id')->filter()->values();
        $contacts = Contact::whereIn('id', $contactIds)->get()->keyBy('id');

        $topContacts = $topContactsRaw->map(function ($row) use ($contacts) {
            return [
                'contact' => $row->contact_id ? $contacts->get($row->contact_id) : null,
                'total_calls' => (int) $row->total_calls,
                'total_duration' => (int) $row->total_duration,
                'total_cost' => (float) $row->total_cost,
            ];
        });

        // Get quality metrics trends
        $qualityTrends = \App\Models\CallQualityMetric::selectRaw(
            'AVG(network_quality_score) as avg_mos, AVG(answer_latency_ms) as avg_latency, DATE(created_at) as date'
        )
            ->whereHas('whatsappCall', function($q) use ($team) {
                $q->where('team_id', $team->id);
            })
            ->whereBetween('created_at', $this->dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('livewire.calls.call-analytics', [
            'statistics' => $statistics,
            'billingStats' => $billingStats,
            'usageLimits' => $usageLimits,
            'costBreakdown' => $costBreakdown,
            'topContacts' => $topContacts,
            'qualityTrends' => $qualityTrends,
        ]);
    }
}
