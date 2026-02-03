<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use App\Models\TeamTransaction;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\TeamWallet;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;

#[Title('Analytics')]
class AnalyticsDashboard extends Component
{
    public $dateRange = 30; // days
    public $chartData = []; // Restored
    public $lastRefresh; // Restored
    public $metaAnalytics = [];

    public function render()
    {
        $team = auth()->user()->currentTeam;
        $teamId = $team->id;
        $cachePrefix = "analytics:team:{$teamId}";

        // 1. Wallet (lightweight, ok to fetch)
        $wallet = TeamWallet::firstOrCreate(['team_id' => $teamId]);

        // 2. Usage Stats (cached briefly to reduce repeated queries per re-render)
        $stats = cache()->remember("{$cachePrefix}:stats:{$this->dateRange}", 60, function () use ($teamId) {
            $start = now()->subDays(30);

            return [
                'msgSent' => Message::where('team_id', $teamId)
                    ->where('direction', 'outbound')
                    ->where('created_at', '>=', $start)
                    ->count(),
                'msgReceived' => Message::where('team_id', $teamId)
                    ->where('direction', 'inbound')
                    ->where('created_at', '>=', $start)
                    ->count(),
                'ticketsResolved' => Ticket::where('team_id', $teamId)
                    ->where('status', 'resolved')
                    ->count(),
                'transactions' => TeamTransaction::where('team_id', $teamId)
                    ->latest()
                    ->take(10)
                    ->get(),
                'lastUpdated' => Message::where('team_id', $teamId)->latest()->value('updated_at') ?? now(),
            ];
        });

        // 3. Official Meta Analytics (cached longer to avoid frequent API calls)
        $this->metaAnalytics = cache()->remember("{$cachePrefix}:meta:{$this->dateRange}", 300, function () use ($team) {
            $waService = new \App\Services\WhatsAppService($team);
            try {
                $metaData = $waService->getAnalytics(
                    now()->subDays(30),
                    now(),
                    'DAILY',
                    ['conversation_analytics', 'cost']
                );
                return $metaData['data'] ?? [];
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to fetch Meta analytics: " . $e->getMessage());
                return [];
            }
        });

        $this->chartData = cache()->remember("{$cachePrefix}:chart:{$this->dateRange}", 120, function () use ($teamId) {
            return $this->buildChartData($teamId);
        });

        return view('livewire.analytics.analytics-dashboard', [
            'wallet' => $wallet,
            'msgSent' => $stats['msgSent'],
            'msgReceived' => $stats['msgReceived'],
            'ticketsResolved' => $stats['ticketsResolved'],
            'transactions' => $stats['transactions'],
            'metaAnalytics' => $this->metaAnalytics,
            'isScheduled' => \App\Models\ScheduledReport::where('user_id', auth()->id())
                ->where('report_type', 'monthly_usage')->exists(),
            'lastUpdated' => $stats['lastUpdated'],
        ]);
    }

    public function mount()
    {
        $this->lastRefresh = now()->format('H:i:s');
    }

    public function refreshData()
    {
        $this->lastRefresh = now()->format('H:i:s');
        // Livewire will re-render
    }

    protected function loadChartData($teamId)
    {
        $this->chartData = $this->buildChartData($teamId);
    }

    protected function buildChartData($teamId)
    {
        $startDate = now()->subDays($this->dateRange);

        $raw = Message::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, direction, count(*) as count')
            ->groupBy('date', 'direction')
            ->orderBy('date')
            ->get();

        $dates = [];
        for ($i = $this->dateRange - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $dates[$d] = ['inbound' => 0, 'outbound' => 0];
        }

        foreach ($raw as $r) {
            $d = $r->date;
            if (isset($dates[$d])) {
                $dates[$d][$r->direction] = $r->count;
            }
        }

        return [
            'labels' => array_keys($dates),
            'datasets' => [
                [
                    'label' => 'Sent',
                    'data' => array_column($dates, 'outbound'),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.4)',
                ],
                [
                    'label' => 'Received',
                    'data' => array_column($dates, 'inbound'),
                    'borderColor' => '#14b8a6',
                    'backgroundColor' => 'rgba(20, 184, 166, 0.4)',
                ]
            ]
        ];
    }

    public function exportTransactions()
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Amount', 'Description', 'Invoice']);

            \App\Models\TeamTransaction::where('team_id', auth()->user()->currentTeam->id)
                ->chunk(100, function ($txns) use ($handle) {
                    foreach ($txns as $txn) {
                        fputcsv($handle, [
                            $txn->created_at->format('Y-m-d H:i:s'),
                            $txn->type,
                            $txn->amount,
                            $txn->description,
                            $txn->invoice_number
                        ]);
                    }
                });
            fclose($handle);
        }, 'transactions.csv');
    }

    public function toggleSchedule()
    {
        $userId = auth()->id();
        $teamId = auth()->user()->currentTeam->id;

        $existing = \App\Models\ScheduledReport::where('user_id', $userId)
            ->where('report_type', 'monthly_usage')
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            \App\Models\ScheduledReport::create([
                'team_id' => $teamId,
                'user_id' => $userId,
                'report_type' => 'monthly_usage',
                'frequency' => 'weekly' // Default
            ]);
        }
    }
}
