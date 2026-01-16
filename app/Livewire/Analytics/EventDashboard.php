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

    protected $queryString = ['searchTerm', 'filterEventType', 'filterCategory', 'filterDateRange'];

    public function render()
    {
        $teamId = Auth::user()->currentTeam->id;
        $days = (int) ($this->filterDateRange ?: 30);

        $currentStart = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);
        $previousEnd = now()->subDays($days);

        // Get events with filters
        $events = CustomerEvent::where('team_id', $teamId)
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->where('event_type', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('event_data', 'like', '%' . $this->searchTerm . '%')
                        ->orWhereHas('contact', function ($cq) {
                            $cq->where('name', 'like', '%' . $this->searchTerm . '%')
                                ->orWhere('phone_number', 'like', '%' . $this->searchTerm . '%');
                        });
                });
            })
            ->when($this->filterEventType !== 'all', function ($query) {
                $query->where('event_type', $this->filterEventType);
            })
            ->when($this->filterCategory !== 'all', function ($query) {
                $query->whereHas('contact', function ($cq) {
                    $cq->where('category_id', $this->filterCategory);
                });
            })
            ->when($this->filterDateRange, function ($query) {
                $query->where('created_at', '>=', now()->subDays((int) $this->filterDateRange));
            })
            ->with('contact.category')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Current period stats
        $currentStats = CustomerEvent::where('team_id', $teamId)
            ->where('created_at', '>=', $currentStart)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get();

        // Previous period stats for comparison
        $previousStats = CustomerEvent::where('team_id', $teamId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->get();

        $totalEvents = $currentStats->sum('count');
        $prevTotalEvents = $previousStats->sum('count');
        $growth = $prevTotalEvents > 0 ? (($totalEvents - $prevTotalEvents) / $prevTotalEvents) * 100 : 0;

        // Chart Data
        $timelineData = CustomerEvent::where('team_id', $teamId)
            ->where('created_at', '>=', $currentStart)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
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

        // Distribution Data
        $distData = [
            'labels' => $currentStats->pluck('event_type')->map(fn($t) => strtoupper(str_replace('_', ' ', $t)))->toArray(),
            'data' => $currentStats->pluck('count')->toArray(),
        ];

        $categories = \App\Models\Category::where('team_id', $teamId)->get();

        $this->dispatch('refreshCharts', chartData: $chartData, distData: $distData);

        return view('livewire.analytics.event-dashboard', [
            'events' => $events,
            'totalEvents' => $totalEvents,
            'growth' => $growth,
            'eventStats' => $currentStats,
            'chartData' => $chartData,
            'distData' => $distData,
            'categories' => $categories,
        ]);
    }

    public function viewEventDetails($eventId)
    {
        $this->selectedEvent = CustomerEvent::with('contact')->find($eventId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedEvent = null;
    }

    public function exportEvents()
    {
        $teamId = Auth::user()->currentTeam->id;
        $events = CustomerEvent::where('team_id', $teamId)
            ->when($this->filterEventType !== 'all', fn($q) => $q->where('event_type', $this->filterEventType))
            ->when($this->filterDateRange, fn($q) => $q->where('created_at', '>=', now()->subDays((int) $this->filterDateRange)))
            ->with('contact')
            ->get();

        $filename = "events_export_" . now()->format('Y-m-d') . ".csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($events) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Timestamp', 'Event Type', 'Contact Name', 'Contact Phone', 'Data']);

            foreach ($events as $event) {
                fputcsv($file, [
                    $event->id,
                    $event->created_at,
                    $event->event_type,
                    $event->contact->name ?? 'N/A',
                    $event->contact->phone_number ?? 'N/A',
                    json_encode($event->event_data)
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
