<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use App\Models\AutomationStepLedger;
use App\Models\Message;
use App\Services\AutomationAnalyticsService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Automation Analytics')]
class AutomationAnalytics extends Component
{
    public Automation $automation;
    public array $dashboard = []; // Contains flow performance data
    public string $currencySymbol = '$'; // Currency symbol for revenue display
    public int $dateRange = 30; // Number of days to look back in analytics
    public string $messageStatusFilter = 'all'; // Filter: all, sent, delivered, read, or failed
    public int $messageDetailsLimit = 100; // Max rows to show in message table
    public string $stepNodeFilter = 'all'; // Selected step to filter contacts by

    public array $messageSummary = [
        'sent' => 0,
        'delivered' => 0,
        'read' => 0,
        'failed' => 0,
        'attempted' => 0,
        'delivery_rate' => 0,
        'read_rate' => 0,
    ];

    public Collection $messageDetails;
    public Collection $recentRuns;
    public ?int $selectedRunId = null;
    public ?object $selectedRun = null;
    public bool $showRunLogModal = false;

    public function mount(int $automationId, AutomationAnalyticsService $analytics): void
    {
        $teamId = auth()->user()->currentTeam->id;

        $this->automation = Automation::query()
            ->where('team_id', $teamId)
            ->findOrFail($automationId);

        $this->dashboard = $analytics->buildDashboard($this->automation);
        $this->currencySymbol = (string) get_setting('currency_symbol', '$');
        $this->messageDetails = collect();
        $this->recentRuns = collect();
        $this->loadMessageReport();
        $this->loadRecentRuns();
    }

    public function refreshData(AutomationAnalyticsService $analytics): void
    {
        // Reload all flow performance data from the database
        $this->dashboard = $analytics->buildDashboard($this->automation->fresh());
        $this->loadMessageReport();
        $this->dispatch('notify', 'Automation analytics refreshed.');
    }

    public function updatedDateRange(): void
    {
        $this->loadMessageReport();
    }

    public function updatedMessageStatusFilter(): void
    {
        $this->loadMessageReport();
    }

    public function exportMessageReport()
    {
        // Download all messages sent through this flow as a CSV file filtered by date and status
        $statusFilter = $this->normalizeMessageStatusFilter();
        $startDate = now()->subDays($this->dateRange);

        return response()->streamDownload(function () use ($statusFilter, $startDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Automation',
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

            Message::query()
                ->with('contact:id,name,phone_number,email')
                ->where('team_id', $this->automation->team_id)
                ->where('automation_id', $this->automation->id)
                ->where('direction', 'outbound')
                ->where('created_at', '>=', $startDate)
                ->whereIn('status', ['sent', 'delivered', 'read', 'failed'])
                ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                    $query->where('status', $statusFilter);
                })
                ->orderByDesc('created_at')
                ->chunk(500, function ($messages) use ($handle) {
                    foreach ($messages as $message) {
                        fputcsv($handle, [
                            $this->automation->name,
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
        }, 'automation-' . $this->automation->id . '-message-report.csv');
    }

    public function exportStepContactsReport()
    {
        // Download list of all contacts that reached each step in this flow
        $startDate = now()->subDays($this->dateRange);
        $selectedNode = $this->normalizeStepNodeFilter();
        $funnelRows = $this->dashboard['funnel'] ?? [];
        $labelsByNode = [];

        foreach ($funnelRows as $row) {
            $labelsByNode[(string) ($row['node_id'] ?? '')] = $row['label'] ?? ($row['node_id'] ?? '');
        }

        return response()->streamDownload(function () use ($startDate, $selectedNode, $labelsByNode) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Automation',
                'Run ID',
                'Node ID',
                'Step Label',
                'Reached At',
                'Contact ID',
                'Contact Name',
                'Contact Number',
                'Contact Email',
            ]);

            AutomationStepLedger::query()
                ->join('automation_runs', 'automation_runs.id', '=', 'automation_step_ledger.automation_run_id')
                ->join('contacts', 'contacts.id', '=', 'automation_runs.contact_id')
                ->where('automation_runs.automation_id', $this->automation->id)
                ->where('automation_step_ledger.status', 'success')
                ->where('automation_step_ledger.execution_key', 'not like', '%_wait')
                ->where('automation_step_ledger.created_at', '>=', $startDate)
                ->when($selectedNode !== 'all', function ($query) use ($selectedNode) {
                    $query->where('automation_step_ledger.node_id', $selectedNode);
                })
                ->orderBy('automation_step_ledger.node_id')
                ->orderByDesc('automation_step_ledger.created_at')
                ->select([
                    'automation_runs.id as run_id',
                    'automation_step_ledger.node_id',
                    'automation_step_ledger.created_at as reached_at',
                    'contacts.id as contact_id',
                    'contacts.name as contact_name',
                    'contacts.phone_number as contact_phone_number',
                    'contacts.email as contact_email',
                ])
                ->chunk(500, function ($rows) use ($handle, $labelsByNode) {
                    foreach ($rows as $row) {
                        $nodeId = (string) $row->node_id;
                        fputcsv($handle, [
                            $this->automation->name,
                            $row->run_id,
                            $nodeId,
                            $labelsByNode[$nodeId] ?? $nodeId,
                            $row->reached_at ? \Carbon\Carbon::parse($row->reached_at)->format('Y-m-d H:i:s') : null,
                            $row->contact_id,
                            $row->contact_name,
                            $row->contact_phone_number,
                            $row->contact_email,
                        ]);
                    }
                });

            fclose($handle);
        }, 'automation-' . $this->automation->id . '-step-contacts.csv');
    }

    protected function loadMessageReport(): void
    {
        $startDate = now()->subDays($this->dateRange);
        $statusFilter = $this->normalizeMessageStatusFilter();

        $summary = Message::query()
            ->where('team_id', $this->automation->team_id)
            ->where('automation_id', $this->automation->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['sent', 'delivered', 'read', 'failed'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sent = (int) ($summary['sent'] ?? 0);
        $delivered = (int) ($summary['delivered'] ?? 0);
        $read = (int) ($summary['read'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);
        $attempted = $sent + $delivered + $read + $failed;
        $deliveredOrRead = $delivered + $read;

        $this->messageSummary = [
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'failed' => $failed,
            'attempted' => $attempted,
            'delivery_rate' => $attempted > 0 ? round(($deliveredOrRead / $attempted) * 100, 1) : 0,
            'read_rate' => $attempted > 0 ? round(($read / $attempted) * 100, 1) : 0,
        ];

        $this->messageDetails = Message::query()
            ->with('contact:id,name,phone_number,email')
            ->where('team_id', $this->automation->team_id)
            ->where('automation_id', $this->automation->id)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['sent', 'delivered', 'read', 'failed'])
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->latest()
            ->limit($this->messageDetailsLimit)
                ->get();
    }

    protected function loadRecentRuns(): void
    {
        $this->recentRuns = \App\Models\AutomationRun::query()
            ->with('contact:id,name,phone_number')
            ->where('automation_id', $this->automation->id)
            ->where('created_at', '>=', now()->subDays($this->dateRange))
            ->latest()
            ->limit(20)
            ->get();
    }

    public function viewRunLog(int $runId): void
    {
        $this->selectedRunId = $runId;
        $this->selectedRun = \App\Models\AutomationRun::query()
            ->with(['ledger' => fn($q) => $q->orderBy('created_at')])
            ->findOrFail($runId);
        $this->showRunLogModal = true;
    }

    protected function normalizeMessageStatusFilter(): string
    {
        $allowed = ['all', 'sent', 'delivered', 'read', 'failed'];
        return in_array($this->messageStatusFilter, $allowed, true) ? $this->messageStatusFilter : 'all';
    }

    protected function normalizeStepNodeFilter(): string
    {
        if ($this->stepNodeFilter === 'all') {
            return 'all';
        }

        $allowedNodes = collect($this->dashboard['funnel'] ?? [])->pluck('node_id')->map(fn($id) => (string) $id)->all();
        return in_array((string) $this->stepNodeFilter, $allowedNodes, true) ? (string) $this->stepNodeFilter : 'all';
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.automations.automation-analytics');
    }
}
