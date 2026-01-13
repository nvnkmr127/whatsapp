<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\ChatMessage;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $stats = [];

    public $systemStatus = [];

    public $chartData = [];

    public $timeRange = 'today';

    public $dashboardData = [];

    // Optimize by reducing the number of database queries
    public function mount()
    {
        // Fetch all counts in a single query for each model
        $totalCounts = [
            'total_message'   => ChatMessage::count(),
            'total_contact'   => Contact::count(),
            'total_campaign'  => Campaign::count(),
            'total_template'  => WhatsappTemplate::count(),
            'todays_message'  => ChatMessage::whereDate('time_sent', Carbon::today())->count(),
            'contact_active'  => Contact::where('is_enabled', 1)->count(),
            'active_campaign' => Campaign::where('is_sent', 0)->count(),
            'active_template' => WhatsappTemplate::where('status', 'APPROVED')->count(),
        ];

        $this->dashboardData = $totalCounts;

        // Prepare stats and system status
        $this->prepareStats($totalCounts);
        $this->prepareSystemStatus();

        // Initial load of chart data
        $this->loadMessageStats();
    }

    // Refactored method to prepare stats
    protected function prepareStats(array $counts)
    {
        $this->stats = [
            [
                'id'           => 1,
                'header'       => 'Messages',
                'header_value' => $counts['total_message'],
                'title'        => 'Messages Today',
                'value'        => $counts['todays_message'],
                'icon'         => 'message-circle',
                'color'        => 'blue',
            ],
            [
                'id'           => 2,
                'header'       => 'Contacts',
                'header_value' => $counts['total_contact'],
                'title'        => 'Active Contacts',
                'value'        => $counts['contact_active'],
                'icon'         => 'users',
                'color'        => 'purple',
            ],
            [
                'id'           => 3,
                'header'       => 'Campaigns',
                'header_value' => $counts['total_campaign'],
                'title'        => 'Active Campaigns',
                'value'        => $counts['active_campaign'],
                'icon'         => 'megaphone',
                'color'        => 'green',
            ],
            [
                'id'           => 4,
                'header'       => 'Templates',
                'header_value' => $counts['total_template'],
                'title'        => 'Active Templates',
                'value'        => $counts['active_template'],
                'icon'         => 'file-text',
                'color'        => 'orange',
            ],
        ];
    }

    // Simplified system status preparation
    protected function prepareSystemStatus()
    {
        $this->systemStatus = [
            'whatsapp_connected' => true,
            'api_status'         => 'active',
            'queue_size'         => 5,
            'daily_api_calls'    => 1250,
        ];
    }

    // Optimized method to update time range
    public function updateTimeRange($range)
    {
        // Validate the incoming range
        $allowedRanges = ['today', 'yesterday', 'this_week', 'last_week', 'month'];
        if (! in_array($range, $allowedRanges)) {
            return;
        }

        // Update the time range
        $this->timeRange = $range;

        // Reload message stats
        $this->loadMessageStats();
    }

    // Refactored message stats loading with improved performance
    public function loadMessageStats()
    {
        $now = Carbon::now();

        // Configure date ranges based on selected time range
        [$startDate, $endDate, $mysqlFormat, $displayType] = $this->getDateRangeConfig($now);

        // Prepare the query with optimized database interaction
        $whatsappMessages = $this->getMessageCountsByPeriod($startDate, $endDate, $mysqlFormat);

        // Generate periods and labels
        [$periods, $displayLabels] = $this->generatePeriodsAndLabels($startDate, $endDate, $displayType);

        // Fill in actual data
        $this->fillPeriodsWithMessageCounts($periods, $whatsappMessages);

        // Prepare chart data
        $this->chartData = [
            'labels' => $displayLabels,
            'series' => [
                [
                    'name' => 'WhatsApp',
                    'data' => array_values($periods),
                ],
            ],
        ];

        // Dispatch event with chart data
        $this->dispatch('chartDataUpdated', $this->chartData);
    }

    // Extract date range configuration
    protected function getDateRangeConfig(Carbon $now): array
    {
        $startDate   = $now->copy()->startOfDay();
        $endDate     = $now->copy()->endOfDay();
        $mysqlFormat = '%H';
        $displayType = 'hours';

        switch ($this->timeRange) {
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate   = $now->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $startDate   = $now->copy()->startOfWeek();
                $endDate     = $now->copy()->endOfWeek();
                $mysqlFormat = '%w';
                $displayType = 'days';
                break;
            case 'last_week':
                $startDate   = $now->copy()->subWeek()->startOfWeek();
                $endDate     = $now->copy()->subWeek()->endOfWeek();
                $mysqlFormat = '%w';
                $displayType = 'days';
                break;
            case 'month':
                $startDate   = $now->copy()->startOfMonth();
                $endDate     = $now->copy()->endOfMonth();
                $mysqlFormat = '%d';
                $displayType = 'days';
                break;
        }

        return [$startDate, $endDate, $mysqlFormat, $displayType];
    }

    // Get message counts by period
    protected function getMessageCountsByPeriod(Carbon $startDate, Carbon $endDate, string $mysqlFormat): array
    {
        return ChatMessage::select(
            DB::raw("DATE_FORMAT(time_sent, '{$mysqlFormat}') as period"),
            DB::raw('COUNT(*) as count')
        )
            ->where('time_sent', '>=', $startDate)
            ->where('time_sent', '<=', $endDate)
            ->groupBy('period')
            ->orderBy(DB::raw('MIN(time_sent)'))
            ->pluck('count', 'period')
            ->toArray();
    }

    // Generate periods and labels
    protected function generatePeriodsAndLabels(Carbon $startDate, Carbon $endDate, string $displayType): array
    {
        $periods       = [];
        $displayLabels = [];
        $current       = $startDate->copy();

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
                    : $current->format('d'));

            $displayType === 'hours' ? $current->addHour() : $current->addDay();
        }

        return [$periods, $displayLabels];
    }

    // Fill periods with message counts
    protected function fillPeriodsWithMessageCounts(array &$periods, array $whatsappMessages)
    {
        foreach ($whatsappMessages as $period => $count) {
            if (isset($periods[$period])) {
                $periods[$period] = (int) $count;
            }
        }
    }

    // Optional logging method
    protected function logChartData(array $whatsappMessages)
    {
        if (function_exists('whatsapp_log')) {
            whatsapp_log(
                'Chart data prepared',
                'info',
                [
                    'chartData'   => $this->chartData,
                    'rawMessages' => $whatsappMessages,
                ]
            );
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
