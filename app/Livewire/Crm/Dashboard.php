<?php

namespace App\Livewire\Crm;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $timeRange = '30'; // days

    public function render()
    {
        $teamId = auth()->user()->currentTeam->id;
        $startDate = now()->subDays($this->timeRange);

        // Stats
        $stats = [
            'total_contacts' => Contact::where('team_id', $teamId)->count(),
            'new_contacts' => Contact::where('team_id', $teamId)->where('created_at', '>=', $startDate)->count(),
            'open_deals_value' => Deal::where('team_id', $teamId)->where('status', 'open')->sum('value'),
            'won_deals_value' => Deal::where('team_id', $teamId)->where('status', 'won')->where('actual_close_date', '>=', $startDate)->sum('value'),
            'conversion_rate' => $this->calculateConversionRate($teamId, $startDate),
        ];

        // Pipeline Distribution
        $pipelineDistribution = Pipeline::where('team_id', $teamId)
            ->with(['stages' => function ($q) {
                $q->withCount(['deals' => function ($d) {
                    $d->where('status', 'open');
                }]);
            }])
            ->get()
            ->flatMap(function ($pipeline) {
                return $pipeline->stages->map(function ($stage) {
                    return [
                        'name' => $stage->name,
                        'value' => $stage->openDeals()->sum('value'),
                        'count' => $stage->deals_count,
                    ];
                });
            });

        // Recent Activities
        $recentDeals = Deal::where('team_id', $teamId)
            ->with(['contact', 'owner', 'stage'])
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.crm.dashboard', [
            'stats' => $stats,
            'pipelineDistribution' => $pipelineDistribution,
            'recentDeals' => $recentDeals,
        ]);
    }

    protected function calculateConversionRate($teamId, $startDate)
    {
        $totalClosed = Deal::where('team_id', $teamId)
            ->whereIn('status', ['won', 'lost'])
            ->where('actual_close_date', '>=', $startDate)
            ->count();

        if ($totalClosed === 0) {
            return 0;
        }

        $won = Deal::where('team_id', $teamId)
            ->where('status', 'won')
            ->where('actual_close_date', '>=', $startDate)
            ->count();

        return round(($won / $totalClosed) * 100, 1);
    }
}
