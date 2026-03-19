<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TeamTransaction;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\TeamWallet;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;

#[Title('Analytics')]
class AnalyticsDashboard extends Component
{
    use WithPagination;

    public $dateRange = 30; // days
    public $chartData = []; // Restored
    public $lastRefresh; // Restored
    public $metaAnalytics = [];
    public $walletId;
    public $webhookStatusFilter = 'all';
    public $webhookSearch = '';
    public $webhookContactFilter = '';
    public $webhookFromDate = '';
    public $webhookToDate = '';
    public $webhookPerPage = 15;
    public $transactionsPerPage = 10;

    protected array $webhookStatuses = ['sent', 'delivered', 'read', 'failed'];

    public function updatingDateRange()
    {
        $this->resetPage('webhookPage');
        $this->resetPage('transactionsPage');
    }

    public function updatingWebhookStatusFilter()
    {
        $this->resetPage('webhookPage');
    }

    public function updatingWebhookSearch()
    {
        $this->resetPage('webhookPage');
    }

    public function updatingWebhookContactFilter()
    {
        $this->resetPage('webhookPage');
    }

    public function updatingWebhookFromDate()
    {
        $this->resetPage('webhookPage');
    }

    public function updatingWebhookToDate()
    {
        $this->resetPage('webhookPage');
    }

    public function updatingWebhookPerPage()
    {
        $this->resetPage('webhookPage');
    }

    public function updatingTransactionsPerPage()
    {
        $this->resetPage('transactionsPage');
    }

    public function render()
    {
        $team = auth()->user()->currentTeam;
        $teamId = $team->id;
        $cachePrefix = "analytics:team:{$teamId}";

        // 1. Wallet (avoid writes during render)
        $wallet = $this->walletId ? TeamWallet::find($this->walletId) : TeamWallet::where('team_id', $teamId)->first();

        // 2. Usage Stats (cached briefly to reduce repeated queries per re-render)
        $stats = cache()->remember("{$cachePrefix}:stats:{$this->dateRange}", 60, function () use ($teamId) {
            $start = now()->subDays($this->dateRange);

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
                'qrScans' => \App\Models\LeadCaptureWidget::where('team_id', $teamId)->sum('scan_count'),
                'qrConversions' => \App\Models\LeadCaptureWidget::where('team_id', $teamId)->sum('conversion_count'),
                'lastUpdated' => Message::where('team_id', $teamId)->latest()->value('updated_at') ?? now(),
            ];
        });

        $webhookSummary = cache()->remember("{$cachePrefix}:webhook-summary:{$this->dateRange}", 60, function () use ($teamId) {
            return $this->buildWebhookSummary($teamId);
        });

        $transactions = TeamTransaction::query()
            ->where('team_id', $teamId)
            ->latest()
            ->paginate((int) $this->transactionsPerPage, ['*'], 'transactionsPage');

        $webhookDetails = $this->webhookDetailsQuery($teamId)
            ->paginate((int) $this->webhookPerPage, ['*'], 'webhookPage');

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
            'qrScans' => $stats['qrScans'],
            'qrConversions' => $stats['qrConversions'],
            'transactions' => $transactions,
            'metaAnalytics' => $this->metaAnalytics,
            'isScheduled' => \App\Models\ScheduledReport::where('user_id', auth()->id())
                ->where('report_type', 'monthly_usage')->exists(),
            'lastUpdated' => $stats['lastUpdated'],
            'webhookSummary' => $webhookSummary,
            'webhookDetails' => $webhookDetails,
        ]);
    }

    public function mount()
    {
        $this->lastRefresh = now()->format('H:i:s');
        $teamId = auth()->user()->currentTeam->id;
        $this->walletId = TeamWallet::firstOrCreate(['team_id' => $teamId])->id;
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

    public function exportWebhookReport()
    {
        $teamId = auth()->user()->currentTeam->id;

        return response()->streamDownload(function () use ($teamId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Created At',
                'Message ID',
                'Direction',
                'Status',
                'Contact Name',
                'Contact Number',
                'Contact Email',
                'Sent At',
                'Delivered At',
                'Read At',
                'Error Message',
                'Message Content',
            ]);

            $this->webhookDetailsQuery($teamId, false)
                ->orderByDesc('created_at')
                ->chunk(500, function ($messages) use ($handle) {
                    foreach ($messages as $message) {
                        fputcsv($handle, [
                            optional($message->created_at)->format('Y-m-d H:i:s'),
                            $message->whatsapp_message_id,
                            $message->direction,
                            $message->status,
                            optional($message->contact)->name,
                            optional($message->contact)->phone_number,
                            optional($message->contact)->email,
                            optional($message->sent_at)->format('Y-m-d H:i:s'),
                            optional($message->delivered_at)->format('Y-m-d H:i:s'),
                            optional($message->read_at)->format('Y-m-d H:i:s'),
                            $message->error_message,
                            $message->content,
                        ]);
                    }
                });

            fclose($handle);
        }, 'webhook-message-report.csv');
    }

    protected function buildWebhookSummary(int $teamId): array
    {
        $startDate = now()->subDays($this->dateRange);

        $summary = Message::query()
            ->where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("status, COUNT(*) as total")
            ->whereIn('status', $this->webhookStatuses)
            ->groupBy('status')
            ->pluck('total', 'status');

        $summaryValues = [
            'sent' => (int) ($summary['sent'] ?? 0),
            'delivered' => (int) ($summary['delivered'] ?? 0),
            'read' => (int) ($summary['read'] ?? 0),
            'failed' => (int) ($summary['failed'] ?? 0),
        ];

        $attempted = array_sum($summaryValues);
        $deliveredOrRead = $summaryValues['delivered'] + $summaryValues['read'];

        $deliveryRate = $attempted > 0 ? round(($deliveredOrRead / $attempted) * 100, 1) : 0;
        $readRate = $attempted > 0 ? round(($summaryValues['read'] / $attempted) * 100, 1) : 0;

        return array_merge($summaryValues, [
            'attempted' => $attempted,
            'delivery_rate' => $deliveryRate,
            'read_rate' => $readRate,
        ]);
    }

    protected function webhookDetailsQuery(int $teamId, bool $applySorting = true): Builder
    {
        $statusFilter = $this->normalizeWebhookStatusFilter();
        $startDate = now()->subDays($this->dateRange)->startOfDay();

        $query = Message::query()
            ->with('contact:id,name,phone_number,email')
            ->where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->whereIn('status', $this->webhookStatuses)
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->where('created_at', '>=', $startDate)
            ->when($this->webhookFromDate, function ($query) {
                $query->whereDate('created_at', '>=', $this->webhookFromDate);
            })
            ->when($this->webhookToDate, function ($query) {
                $query->whereDate('created_at', '<=', $this->webhookToDate);
            })
            ->when($this->webhookContactFilter, function ($query) {
                $term = trim($this->webhookContactFilter);
                $query->whereHas('contact', function ($contactQuery) use ($term) {
                    $contactQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('phone_number', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($this->webhookSearch, function ($query) {
                $term = trim($this->webhookSearch);
                $query->where(function ($innerQuery) use ($term) {
                    $innerQuery->where('whatsapp_message_id', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%")
                        ->orWhere('error_message', 'like', "%{$term}%");
                });
            });

        return $applySorting ? $query->latest() : $query;
    }

    protected function normalizeWebhookStatusFilter(): string
    {
        if ($this->webhookStatusFilter === 'all') {
            return 'all';
        }

        return in_array($this->webhookStatusFilter, $this->webhookStatuses, true)
            ? $this->webhookStatusFilter
            : 'all';
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
