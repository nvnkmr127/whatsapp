<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CustomerEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventDashboard extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $filterEventType = 'all';
    public $filterCategory = 'all';
    public $filterDateRange = '7'; // days
    public $selectedEvent = null;
    public $showDetailModal = false;
    public $lastRefresh;

    protected $queryString = ['searchTerm', 'filterEventType', 'filterCategory', 'filterDateRange'];

    public function render()
    {
        $teamId = Auth::user()->currentTeam->id;
        $days = (int) ($this->filterDateRange ?: 30);

        $currentStart = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);
        $previousEnd = now()->subDays($days);

        // Get events with filters (SystemEvents)
        $events = \App\Models\SystemEvent::where('team_id', $teamId)
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->where('event_type', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('payload', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('trace_id', $this->searchTerm);
                });
            })
            ->when($this->filterEventType !== 'all', function ($query) {
                $query->where('event_type', $this->filterEventType);
            })
            ->when($this->filterCategory !== 'all', function ($query) {
                $query->where('category', $this->filterCategory);
            })
            ->when($this->filterDateRange, function ($query) {
                $query->where('occurred_at', '>=', now()->subDays((int) $this->filterDateRange));
            })
            ->orderBy('occurred_at', 'desc')
            ->paginate(15);

        // Current period stats
        $currentStats = \App\Models\SystemEvent::where('team_id', $teamId)
            ->where('occurred_at', '>=', $currentStart)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get();

        // Previous period stats for comparison
        $previousStats = \App\Models\SystemEvent::where('team_id', $teamId)
            ->whereBetween('occurred_at', [$previousStart, $previousEnd])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get();

        $totalEvents = $currentStats->sum('count');
        $prevTotalEvents = $previousStats->sum('count');
        $growth = $prevTotalEvents > 0 ? (($totalEvents - $prevTotalEvents) / $prevTotalEvents) * 100 : 0;

        // Chart Data
        $timelineData = \App\Models\SystemEvent::where('team_id', $teamId)
            ->where('occurred_at', '>=', $currentStart)
            ->select(DB::raw('DATE(occurred_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $chartData = [
            'labels' => $timelineData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Events',
                    'data' => $timelineData->pluck('count')->toArray(),
                ]
            ]
        ];

        // Distribution Data (By Category or Type)
        $distData = [
            'labels' => $currentStats->pluck('event_type')->map(fn($t) => strtoupper(class_basename($t)))->toArray(),
            'data' => $currentStats->pluck('count')->toArray(),
        ];

        $categories = collect([
            (object) ['id' => 'business', 'name' => 'Business'],
            (object) ['id' => 'operational', 'name' => 'Operational'],
            (object) ['id' => 'debug', 'name' => 'Debug']
        ]);

        $this->dispatch('refreshCharts', chartData: $chartData, distData: $distData);

        return view('livewire.analytics.event-dashboard', [
            'events' => $events,
            'totalEvents' => $totalEvents,
            'growth' => $growth,
            'eventStats' => $currentStats,
            'chartData' => $chartData,
            'distData' => $distData,
            'categories' => $categories,
            'lastUpdated' => \App\Models\SystemEvent::latest('occurred_at')->value('occurred_at') ?? now()
        ]);
    }

    public function viewEventDetails($eventId)
    {
        // Redirect to Explorer for drill-down
        $event = \App\Models\SystemEvent::find($eventId);
        if ($event) {
            return redirect()->route('analytics.explorer', ['filterTraceId' => $event->trace_id]);
        }
    }

    public function exportEvents()
    {
        $teamId = Auth::user()->currentTeam->id;
        $events = \App\Models\SystemEvent::where('team_id', $teamId)
            ->when($this->filterEventType !== 'all', fn($q) => $q->where('event_type', $this->filterEventType))
            ->limit(1000)
            ->get();

        // ... Export Logic (Simplified for brevity) ...
        return response()->streamDownload(function () use ($events) {
            echo "ID,Time,Type,Category,Payload\n";
            foreach ($events as $e) {
                echo "{$e->id},{$e->occurred_at},{$e->event_type},{$e->category},\"" . addslashes(json_encode($e->payload)) . "\"\n";
            }
        }, 'events.csv');
    }

    public function mount()
    {
        $this->lastRefresh = now()->format('H:i:s');
    }

    public function refreshData()
    {
        $this->lastRefresh = now()->format('H:i:s');
    }

    // viewEventDetails and exportEvents are already defined above via previous edit
}
