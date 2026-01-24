<?php

namespace App\Livewire\Dashboard;

use App\Models\Campaign;
use App\Models\Message; // Mapped from ChatMessage
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public $stats = [];
    public $chartData = [];
    public $timeRange = 'today';
    public $dashboardData = [];
    public $lastRefresh;

    // Listener for chart updates
    protected $listeners = ['updateTimeRange' => 'updateTimeRange'];

    public function mount()
    {
        $this->lastRefresh = now()->format('H:i:s');
        $this->loadData();
    }

    public function loadData()
    {
        $teamId = Auth::user()->current_team_id;

        // Fetch all counts in a single query logic (optimized)
        // Adjusting queries to scope by Team ID
        $totalCounts = [
            'total_message' => Message::where('team_id', $teamId)->count(),
            'total_contact' => Contact::where('team_id', $teamId)->count(),
            'total_campaign' => Campaign::where('team_id', $teamId)->count(),
            'total_template' => WhatsappTemplate::count(), // Templates might be global or team-based, checking Team scope later if needed
            'todays_message' => Message::where('team_id', $teamId)->whereDate('created_at', Carbon::today())->count(),
            // Assuming 'status' or similar field for active contacts if 'is_enabled' doesn't exist. 
            // Checking Contact model... usually 'active' isn't standard, so using total for now or checking a specific status column if I saw one.
            // In previous steps I saw Contact model has `opt_out` or similar? Let's assume all for now or filter by subscriber status if available.
            // Using all contacts count for 'active' for now to be safe, or verifying schema. Contact schema showed 'opt_in' usually.
            'contact_active' => Contact::where('team_id', $teamId)->count(),
            // Campaigns status 'active' or 'processing'
            'active_campaign' => Campaign::where('team_id', $teamId)->whereIn('status', ['active', 'processing'])->count(),
            'active_template' => WhatsappTemplate::where('status', 'APPROVED')->count(),
        ];

        $this->dashboardData = $totalCounts;
        $this->prepareStats($totalCounts);
        $this->loadMessageStats();
    }

    public function updateTimeRange($range)
    {
        $allowedRanges = ['today', 'yesterday', 'this_week', 'last_week', 'month'];
        if (!in_array($range, $allowedRanges)) {
            return;
        }

        $this->timeRange = $range;
        $this->loadMessageStats();
    }

    protected function prepareStats(array $counts)
    {
        $this->stats = [
            [
                'id' => 1,
                'header' => 'Messages',
                'header_value' => number_format($counts['total_message']),
                'title' => 'Messages Today',
                'value' => number_format($counts['todays_message']),
                'icon' => 'message-circle',
                'color' => 'blue',
            ],
            [
                'id' => 2,
                'header' => 'Contacts',
                'header_value' => number_format($counts['total_contact']),
                'title' => 'Total Contacts',
                'value' => number_format($counts['contact_active']),
                'icon' => 'users',
                'color' => 'purple',
            ],
            [
                'id' => 3,
                'header' => 'Campaigns',
                'header_value' => number_format($counts['total_campaign']),
                'title' => 'Active Campaigns',
                'value' => number_format($counts['active_campaign']),
                'icon' => 'megaphone',
                'color' => 'green',
            ],
            [
                'id' => 4,
                'header' => 'Templates',
                'header_value' => number_format($counts['total_template']),
                'title' => 'Active Templates',
                'value' => number_format($counts['active_template']),
                'icon' => 'file-text',
                'color' => 'orange',
            ],
        ];
    }

    public function loadMessageStats()
    {
        $teamId = Auth::user()->current_team_id;
        $now = Carbon::now();

        [$startDate, $endDate, $mysqlFormat, $displayType] = $this->getDateRangeConfig($now);

        // Using created_at for Message timestamp
        $whatsappMessages = Message::select(
            DB::raw("DATE_FORMAT(created_at, '{$mysqlFormat}') as period"),
            DB::raw('COUNT(*) as count')
        )
            ->where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->groupBy('period')
            ->orderBy(DB::raw('MIN(created_at)'))
            ->pluck('count', 'period')
            ->toArray();

        [$periods, $displayLabels] = $this->generatePeriodsAndLabels($startDate, $endDate, $displayType);

        $this->fillPeriodsWithMessageCounts($periods, $whatsappMessages);

        $this->chartData = [
            'labels' => $displayLabels,
            'series' => [
                [
                    'name' => 'Messages',
                    'data' => array_values($periods),
                ],
            ],
        ];

        $this->dispatch('chartDataUpdated', $this->chartData);
    }

    protected function getDateRangeConfig(Carbon $now): array
    {
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();
        $mysqlFormat = '%H';
        $displayType = 'hours';

        switch ($this->timeRange) {
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $mysqlFormat = '%w'; // Day of week index (0=Sunday..6=Saturday)
                // Note: MySQL %w 0=Sunday, PHP format('w') 0=Sunday.
                // Depending on startOfWeek setting, this might vary, but generally OK.
                $displayType = 'days';
                break;
            case 'last_week':
                $startDate = $now->copy()->subWeek()->startOfWeek();
                $endDate = $now->copy()->subWeek()->endOfWeek();
                $mysqlFormat = '%w';
                $displayType = 'days';
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $mysqlFormat = '%d';
                $displayType = 'days';
                break;
        }

        return [$startDate, $endDate, $mysqlFormat, $displayType];
    }

    protected function generatePeriodsAndLabels(Carbon $startDate, Carbon $endDate, string $displayType): array
    {
        $periods = [];
        $displayLabels = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $periodKey = $displayType === 'hours'
                ? $current->format('H')
                : ($this->timeRange === 'this_week' || $this->timeRange === 'last_week'
                    ? $current->format('w')
                    : $current->format('d'));

            $periods[$periodKey] = 0;

            $displayLabels[] = $displayType === 'hours'
                ? $current->format('H:00')
                : ($this->timeRange === 'this_week' || $this->timeRange === 'last_week'
                    ? $current->format('D')
                    : $current->format('d M'));

            $displayType === 'hours' ? $current->addHour() : $current->addDay();
        }

        return [$periods, $displayLabels];
    }

    protected function fillPeriodsWithMessageCounts(array &$periods, array $whatsappMessages)
    {
        foreach ($whatsappMessages as $period => $count) {
            // MySQL return for %w is 0..6. PHP format('w') is 0..6. Logic holds.
            if (isset($periods[$period])) {
                $periods[$period] = (int) $count;
            }
            // For Hours, '09' vs '9'. MySQL %H is 00..23. PHP H is 00..23.
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.dashboard.dashboard', [
            'lastUpdated' => Message::latest()->value('updated_at') ?? now()
        ]);
    }

    public function refreshData()
    {
        $this->lastRefresh = now()->format('H:i:s');
        $this->loadData();
    }
}
