<?php

namespace App\Livewire\Analytics;

use App\Models\SystemEvent;
use Livewire\Component;
use Livewire\WithPagination;

class EventExplorer extends Component
{
    use WithPagination;

    public $search = '';
    public $filterModule = '';
    public $filterCategory = '';
    public $filterTraceId = '';
    public $showNoise = false; // By default only show Business/Signal
    public $filterEntityId = '';

    // For drill-down modal
    public $selectedEvent = null;
    public $traceEvents = [];
    public $showTraceModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterModule' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterTraceId' => ['except' => ''],
        'showNoise' => ['except' => false],
        'filterEntityId' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = SystemEvent::query()
            ->latest('occurred_at');

        // Noise Filter (Default: Show only Signals)
        if (!$this->showNoise && !$this->filterCategory && !$this->filterTraceId && !$this->search) {
            $query->where('is_signal', true);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('event_type', 'like', '%' . $this->search . '%')
                    ->orWhere('payload', 'like', '%' . $this->search . '%')
                    ->orWhere('trace_id', $this->search);
            });
        }

        if ($this->filterModule) {
            $query->where('source', $this->filterModule);
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        if ($this->filterTraceId) {
            $query->where('trace_id', $this->filterTraceId);
        }

        if ($this->filterEntityId) {
            $query->where(function ($q) {
                $q->where('actor_id', $this->filterEntityId)
                    ->orWhereJsonContains('metadata->contact_id', (int) $this->filterEntityId); // Heuristic
            });
        }

        return view('livewire.analytics.event-explorer', [
            'events' => $query->paginate(20),
            'modules' => SystemEvent::distinct()->pluck('source')->filter()->values(),
        ]);
    }

    public function viewTrace($traceId)
    {
        $this->filterTraceId = $traceId;
        // Optionally open modal directly
        // $this->showTraceModal = true;
        // $this->traceEvents = SystemEvent::where('trace_id', $traceId)->orderBy('occurred_at')->get();
    }

    public function showDetails($eventId)
    {
        $this->selectedEvent = SystemEvent::findOrFail($eventId);
        $this->showTraceModal = true;

        if ($this->selectedEvent->trace_id) {
            $this->traceEvents = SystemEvent::where('trace_id', $this->selectedEvent->trace_id)
                ->orderBy('occurred_at')
                ->get();
        } else {
            $this->traceEvents = collect([$this->selectedEvent]);
        }
    }

    public function closeDetails()
    {
        $this->showTraceModal = false;
        $this->selectedEvent = null;
        $this->traceEvents = [];
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterModule', 'filterCategory', 'filterTraceId', 'filterEntityId', 'showNoise']);
        $this->resetPage();
    }
}
