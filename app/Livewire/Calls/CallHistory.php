<?php

namespace App\Livewire\Calls;

use App\Models\WhatsAppCall;
use App\Services\CallService;
use Livewire\Component;
use Livewire\WithPagination;

class CallHistory extends Component
{
    use WithPagination;

    public $filters = [
        'direction' => '',
        'status' => '',
        'from_date' => '',
        'to_date' => '',
        'search' => '',
    ];

    public $perPage = 15;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public function getListeners()
    {
        if (! auth()->check() || ! auth()->user()->currentTeam) {
            return [];
        }

        $teamId = auth()->user()->currentTeam->id;

        return [
            "echo-private:teams.{$teamId},.call.offered" => '$refresh',
            "echo-private:teams.{$teamId},.call.ended" => '$refresh',
        ];
    }

    protected $queryString = [
        'filters' => ['except' => ['direction' => '', 'status' => '', 'from_date' => '', 'to_date' => '', 'search' => '']],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        // Initialize filters from query string if present
    }

    public function updatingFilters()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->filters = [
            'direction' => '',
            'status' => '',
            'from_date' => '',
            'to_date' => '',
            'search' => '',
        ];
        $this->resetPage();
    }

    public function render()
    {
        $team = auth()->user()->currentTeam;

        if (! $team) {
            return view('livewire.calls.call-history', [
                'calls' => collect(), // Or empty paginator
                'statistics' => [],
                'usageLimits' => [],
                'period' => 'month',
            ]);
        }

        $query = WhatsAppCall::where('team_id', $team->id)
            ->with(['contact:id,name,phone_number', 'qualityMetric']);

        // Apply filters
        if (! empty($this->filters['direction'])) {
            $query->where('direction', $this->filters['direction']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['from_date']);
        }

        if (! empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['to_date']);
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('from_number', 'like', "%{$search}%")
                    ->orWhere('to_number', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $calls = $query->paginate($this->perPage);

        // Get statistics
        $callService = app(CallService::class)->setTeam($team);
        $statistics = $callService->getCallStatistics('month');
        $usageLimits = $callService->checkUsageLimits();

        // Get safeguard status
        $safeguardService = new \App\Services\CallSafeguardService;
        $safeguardStatus = $safeguardService->getStatus($team);

        return view('livewire.calls.call-history', [
            'calls' => $calls,
            'statistics' => $statistics,
            'usageLimits' => $usageLimits,
            'safeguardStatus' => $safeguardStatus,
            'period' => 'month',
        ]);
    }
}
