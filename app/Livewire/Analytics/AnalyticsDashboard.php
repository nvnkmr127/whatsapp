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
    public $chartData = [];

    public function render()
    {
        $teamId = auth()->user()->currentTeam->id;

        // 1. Wallet
        $wallet = TeamWallet::firstOrCreate(['team_id' => $teamId]);

        // 2. Usage Stats
        $msgSent = Message::where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $msgReceived = Message::where('team_id', $teamId)
            ->where('direction', 'inbound')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // 3. Agent Performance (Tickets)
        // Group by User (if tickets assigned to user? Tickets currently generic, strictly assuming assigned via contact owner or logic)
        // For MVP, just Total Tickets Resolved
        $ticketsResolved = Ticket::where('team_id', $teamId)
            ->where('status', 'resolved')
            ->count();

        // 4. Billing History
        $transactions = TeamTransaction::where('team_id', $teamId)
            ->latest()
            ->take(10)
            ->get();

        $this->loadChartData($teamId);

        return view('livewire.analytics.analytics-dashboard', [
            'wallet' => $wallet,
            'msgSent' => $msgSent,
            'msgReceived' => $msgReceived,
            'ticketsResolved' => $ticketsResolved,
            'transactions' => $transactions,
            'isScheduled' => \App\Models\ScheduledReport::where('user_id', auth()->id())
                ->where('report_type', 'monthly_usage')->exists()
        ]);
    }

    protected function loadChartData($teamId)
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

        $this->chartData = [
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
