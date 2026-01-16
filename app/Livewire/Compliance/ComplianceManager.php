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
        $teamId = Auth::user()->currentTeam->id;

        $consentLogs = ConsentLog::where('team_id', $teamId)
            ->when($this->searchTerm, function ($query) {
                $query->whereHas('contact', function ($q) {
                    $q->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('phone', 'like', '%' . $this->searchTerm . '%');
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
        // TODO: Implement CSV export
        session()->flash('message', 'Export functionality coming soon!');
        $this->showExportModal = false;
    }
}
