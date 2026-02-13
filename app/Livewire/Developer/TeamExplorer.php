<?php

namespace App\Livewire\Developer;

use App\Models\Team;
use Livewire\Component;
use Livewire\WithPagination;

class TeamExplorer extends Component
{
    use WithPagination;

    public $search = '';
    public $filterPlan = '';
    public $selectedTeam = null;
    public $showDetailsModal = false;

    protected $queryString = ['search', 'filterPlan'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewTeam($id)
    {
        $this->selectedTeam = Team::with(['owner', 'users'])->findOrFail($id);

        // Add stats for details view
        $this->selectedTeam->stats = [
            'messages_count' => \App\Models\Message::where('team_id', $id)->count(),
            'contacts_count' => \App\Models\Contact::where('team_id', $id)->count(),
            'webhooks_count' => \App\Models\WebhookSubscription::where('team_id', $id)->count(),
            'sources_count' => \App\Models\WebhookSource::where('team_id', $id)->count(),
            'api_tokens_count' => \App\Models\Team::find($id)->owner->tokens()->count(),
        ];

        $this->showDetailsModal = true;
    }

    public function closeDetails()
    {
        $this->showDetailsModal = false;
        $this->selectedTeam = null;
    }

    public function impersonateOwner($teamId)
    {
        $team = Team::findOrFail($teamId);
        return redirect()->route('admin.impersonate.enter', $team->user_id);
    }

    #[\Livewire\Attributes\Layout('components.layouts.app')]
    public function render()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $query = Team::with('owner')
            ->withCount(['users', 'messages', 'contacts'])
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('owner', function ($q) {
                        $q->where('email', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->filterPlan) {
            $query->where('subscription_plan', $this->filterPlan);
        }

        $teams = $query->paginate(12);

        $plans = Team::distinct()->whereNotNull('subscription_plan')->pluck('subscription_plan');

        return view('livewire.developer.team-explorer', [
            'teams' => $teams,
            'plans' => $plans
        ]);
    }
}
