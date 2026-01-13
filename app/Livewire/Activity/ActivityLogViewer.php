<?php

namespace App\Livewire\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogViewer extends Component
{
    use WithPagination;

    public $search = '';
    public $filterUser = '';
    public $filterAction = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterUser' => ['except' => ''],
        'filterAction' => ['except' => ''],
    ];

    public function render()
    {
        $query = ActivityLog::where('team_id', Auth::user()->currentTeam->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                    ->orWhere('action', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterUser) {
            $query->where('user_id', $this->filterUser);
        }

        if ($this->filterAction) {
            $query->where('action', $this->filterAction);
        }

        $logs = $query->with('user')->latest()->paginate(20);
        $users = User::whereHas('teams', function ($q) {
            $q->where('teams.id', Auth::user()->currentTeam->id);
        })->get();

        // Get unique actions for filter
        $actions = ActivityLog::where('team_id', Auth::user()->currentTeam->id)
            ->select('action')
            ->distinct()
            ->pluck('action');

        return view('livewire.activity.activity-log-viewer', compact('logs', 'users', 'actions'));
    }
}
