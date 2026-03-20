<?php

namespace App\Livewire\Crm;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Company;
use App\Models\CrmActivity;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrmDashboard extends Component
{
    public $stats = [];
    public $recentDeals = [];
    public $recentActivities = [];
    public $pipelineDistribution = [];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $teamId = Auth::user()->currentTeam->id;

        $this->stats = [
            'total_contacts' => Contact::where('team_id', $teamId)->count(),
            'active_deals' => Deal::where('team_id', $teamId)->where('status', 'open')->count(),
            'pipeline_value' => Deal::where('team_id', $teamId)->where('status', 'open')->sum('value'),
            'total_companies' => Company::where('team_id', $teamId)->count(),
            'closed_won_today' => Deal::where('team_id', $teamId)
                ->where('status', 'won')
                ->whereDate('updated_at', now()->today())
                ->sum('value'),
        ];

        $this->recentDeals = Deal::where('team_id', $teamId)
            ->with(['contact', 'stage'])
            ->latest()
            ->limit(5)
            ->get();

        $activitiesQuery = CrmActivity::query()
            ->with(['user', 'relatedTo']);

        if (Schema::hasColumn('crm_activities', 'team_id')) {
            $activitiesQuery->where('team_id', $teamId);
        } else {
            $activitiesQuery->whereHas('user', function ($query) use ($teamId) {
                $query->where('current_team_id', $teamId);
            });
        }

        $this->recentActivities = $activitiesQuery
            ->latest()
            ->limit(10)
            ->get();

        // Pipeline Distribution
        $this->pipelineDistribution = DB::table('deals')
            ->join('pipeline_stages', 'deals.stage_id', '=', 'pipeline_stages.id')
            ->where('deals.team_id', $teamId)
            ->where('deals.status', 'open')
            ->select('pipeline_stages.name', DB::raw('count(*) as count'), DB::raw('sum(deals.value) as total_value'))
            ->groupBy('pipeline_stages.name', 'pipeline_stages.position')
            ->orderBy('pipeline_stages.position')
            ->get();
    }

    public function render()
    {
        return view('livewire.crm.crm-dashboard');
    }
}
