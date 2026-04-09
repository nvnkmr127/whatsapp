<?php

namespace App\Livewire\Analytics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CohortAnalysis extends Component
{
    public string $metric = 'orders';   // 'orders' | 'conversations'

    public int $months = 12;

    public array $cohorts = [];

    public array $summary = [];

    public array $trend = [];

    public function mount(): void
    {
        $this->load();
    }

    public function updatedMetric(): void
    {
        $this->load();
    }

    public function updatedMonths(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $teamId = (int) auth()->user()->currentTeam->id;
        $this->cohorts = $this->buildCohorts($teamId);
        $this->buildSummary();
    }

    // -----------------------------------------------------------------------
    // Core query
    // -----------------------------------------------------------------------
    protected function buildCohorts(int $teamId): array
    {
        $driver = DB::getDriverName();

        $monthFmt = $driver === 'sqlite'
            ? "strftime('%Y-%m', contacts.created_at)"
            : "DATE_FORMAT(contacts.created_at, '%Y-%m')";

        $add30 = $driver === 'sqlite'
            ? "datetime(contacts.created_at, '+30 days')"
            : 'DATE_ADD(contacts.created_at, INTERVAL 30 DAY)';
        $add60 = $driver === 'sqlite'
            ? "datetime(contacts.created_at, '+60 days')"
            : 'DATE_ADD(contacts.created_at, INTERVAL 60 DAY)';
        $add90 = $driver === 'sqlite'
            ? "datetime(contacts.created_at, '+90 days')"
            : 'DATE_ADD(contacts.created_at, INTERVAL 90 DAY)';

        $since = Carbon::now()->subMonths($this->months)->startOfMonth();

        if ($this->metric === 'orders') {
            $rows = DB::table('contacts')
                ->leftJoin('orders', function ($join) use ($teamId) {
                    $join->on('orders.contact_id', '=', 'contacts.id')
                        ->where('orders.team_id', '=', $teamId)
                        ->whereIn('orders.status', ['paid', 'shipped', 'completed', 'sent']);
                })
                ->where('contacts.team_id', $teamId)
                ->whereRaw('contacts.created_at >= ?', [$since])
                ->selectRaw("{$monthFmt} as cohort_month")
                ->selectRaw('COUNT(DISTINCT contacts.id) as cohort_size')
                ->selectRaw("COUNT(DISTINCT CASE WHEN orders.created_at <= {$add30} THEN contacts.id END) as conv_30")
                ->selectRaw("COUNT(DISTINCT CASE WHEN orders.created_at <= {$add60} THEN contacts.id END) as conv_60")
                ->selectRaw("COUNT(DISTINCT CASE WHEN orders.created_at <= {$add90} THEN contacts.id END) as conv_90")
                ->groupByRaw($monthFmt)
                ->orderBy('cohort_month', 'desc')
                ->get();
        } else {
            $rows = DB::table('contacts')
                ->leftJoin('conversations', function ($join) use ($teamId) {
                    $join->on('conversations.contact_id', '=', 'contacts.id')
                        ->where('conversations.team_id', '=', $teamId)
                        ->whereColumn('conversations.created_at', '>', 'contacts.created_at');
                })
                ->where('contacts.team_id', $teamId)
                ->whereRaw('contacts.created_at >= ?', [$since])
                ->selectRaw("{$monthFmt} as cohort_month")
                ->selectRaw('COUNT(DISTINCT contacts.id) as cohort_size')
                ->selectRaw("COUNT(DISTINCT CASE WHEN conversations.created_at <= {$add30} THEN contacts.id END) as conv_30")
                ->selectRaw("COUNT(DISTINCT CASE WHEN conversations.created_at <= {$add60} THEN contacts.id END) as conv_60")
                ->selectRaw("COUNT(DISTINCT CASE WHEN conversations.created_at <= {$add90} THEN contacts.id END) as conv_90")
                ->groupByRaw($monthFmt)
                ->orderBy('cohort_month', 'desc')
                ->get();
        }

        if ($rows->isEmpty()) {
            return [];
        }

        $cohorts = $rows->map(function ($row) {
            $size = (int) $row->cohort_size;
            $rate = fn (int $n) => $size > 0 ? round(($n / $size) * 100, 1) : 0.0;
            $c30 = (int) $row->conv_30;
            $c60 = (int) $row->conv_60;
            $c90 = (int) $row->conv_90;

            $date = Carbon::createFromFormat('Y-m', $row->cohort_month);

            return [
                'cohort_month' => $row->cohort_month,
                'cohort_label' => $date->translatedFormat('M Y'),
                'cohort_size' => $size,
                'windows' => [
                    30 => ['converted' => $c30, 'rate' => $rate($c30), 'intensity' => 0],
                    60 => ['converted' => $c60, 'rate' => $rate($c60), 'intensity' => 0],
                    90 => ['converted' => $c90, 'rate' => $rate($c90), 'intensity' => 0],
                ],
            ];
        })->all();

        // Per-column intensity for colour coding
        foreach ([30, 60, 90] as $w) {
            $max = max(array_column(array_column($cohorts, 'windows'), $w) === false
                ? [0]
                : array_map(fn ($c) => $c['windows'][$w]['rate'], $cohorts));
            foreach ($cohorts as &$row) {
                $row['windows'][$w]['intensity'] = $max > 0
                    ? (int) round(($row['windows'][$w]['rate'] / $max) * 100)
                    : 0;
            }
            unset($row);
        }

        return $cohorts;
    }

    protected function buildSummary(): void
    {
        if (empty($this->cohorts)) {
            $this->summary = [];
            $this->trend = [];

            return;
        }

        $collection = collect($this->cohorts);

        $best = $collection->sortByDesc(fn ($c) => $c['windows'][30]['rate'])->first();
        $worst = $collection->sortBy(fn ($c) => $c['windows'][30]['rate'])->first();
        $avg30 = round($collection->avg(fn ($c) => $c['windows'][30]['rate']), 1);

        $this->summary = [
            'total_cohorts' => $collection->count(),
            'total_contacts' => $collection->sum('cohort_size'),
            'avg_30d' => $avg30,
            'best' => $best,
            'worst' => $worst,
        ];

        // Trend: last two cohorts chronologically
        $asc = $collection->sortBy('cohort_month')->values();
        if ($asc->count() >= 2) {
            $prev = $asc->get($asc->count() - 2);
            $last = $asc->last();
            $diff = round($last['windows'][30]['rate'] - $prev['windows'][30]['rate'], 1);
            $this->trend = [
                'prev_label' => $prev['cohort_label'],
                'last_label' => $last['cohort_label'],
                'prev_rate' => $prev['windows'][30]['rate'],
                'last_rate' => $last['windows'][30]['rate'],
                'diff' => $diff,
                'direction' => $diff >= 0 ? 'up' : 'down',
                'label' => $diff >= 0 ? __('Improving Trend') : __('Declining Trend'),
            ];
        } else {
            $this->trend = [];
        }
    }

    public function render()
    {
        return view('livewire.analytics.cohort-analysis');
    }
}
