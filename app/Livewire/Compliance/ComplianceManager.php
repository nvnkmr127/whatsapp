<?php

namespace App\Livewire\Compliance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ConsentLog;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class ComplianceManager extends Component
{
    use WithPagination;

    public $activeTab = 'logs'; // 'logs' or 'registry'
    public $searchTerm = '';
    public $filterStatus = 'all';
    public $filterDateRange = '30';

    public $showExportModal = false;

    public function render()
    {
        if (!Auth::check() || !Auth::user()->currentTeam) {
            return view('livewire.compliance.compliance-manager', [
                'consentLogs' => collect(),
                'stats' => ['total' => 0, 'granted' => 0, 'revoked' => 0, 'rate' => 0],
            ]);
        }

        $teamId = Auth::user()->currentTeam->id;

        $consentLogs = ConsentLog::where('team_id', $teamId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('contact', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('phone_number', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->when($this->filterStatus !== 'all', function ($query) {
                $query->where('action', $this->filterStatus === 'granted' ? 'OPT_IN' : 'OPT_OUT');
            })
            ->when($this->filterDateRange, function ($query) {
                $query->where('created_at', '>=', now()->subDays((int) $this->filterDateRange));
            })
            ->with('contact')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Compliance statistics
        $stats = [
            'total' => ConsentLog::where('team_id', $teamId)->count(),
            'granted' => ConsentLog::where('team_id', $teamId)->where('action', 'OPT_IN')->count(),
            'revoked' => ConsentLog::where('team_id', $teamId)->where('action', 'OPT_OUT')->count(),
            'rate' => 0,
        ];

        if ($stats['total'] > 0) {
            $stats['rate'] = round(($stats['granted'] / $stats['total']) * 100, 1);
        }

        return view('livewire.compliance.compliance-manager', [
            'consentLogs' => $consentLogs,
            'stats' => $stats,
        ]);
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function exportCompliance()
    {
        if (!Auth::check() || !Auth::user()->currentTeam) {
            return;
        }

        $teamId = Auth::user()->currentTeam->id;
        $fileName = 'compliance_logs_' . now()->format('Y_m_d_His') . '.csv';

        // Capture current state variables for the closure
        $searchTerm = $this->searchTerm;
        $filterStatus = $this->filterStatus;
        $filterDateRange = $this->filterDateRange;

        return response()->streamDownload(function () use ($teamId, $searchTerm, $filterStatus, $filterDateRange) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Contact Name', 'Phone', 'Action', 'Source', 'Reason']);

            ConsentLog::where('team_id', $teamId)
                ->when($searchTerm, function ($query) use ($searchTerm) {
                    $query->whereHas('contact', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('phone_number', 'like', '%' . $searchTerm . '%');
                    });
                })
                ->when($filterStatus !== 'all', function ($query) use ($filterStatus) {
                    $query->where('action', $filterStatus === 'granted' ? 'OPT_IN' : 'OPT_OUT');
                })
                ->when($filterDateRange, function ($query) use ($filterDateRange) {
                    $query->where('created_at', '>=', now()->subDays((int) $filterDateRange));
                })
                ->with('contact')
                ->orderBy('created_at', 'desc')
                ->chunk(100, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->created_at->format('Y-m-d H:i:s'),
                            $log->contact->name ?? 'Unknown',
                            $log->contact->phone_number ?? 'Unknown',
                            $log->action,
                            $log->source ?? 'N/A',
                            $log->reason ?? 'N/A',
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName);
    }
}
