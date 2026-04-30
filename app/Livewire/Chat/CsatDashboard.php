<?php

namespace App\Livewire\Chat;

use App\Models\CsatRating;
use App\Models\Team;
use App\Services\CsatService;
use Livewire\Component;
use Livewire\Attributes\Computed;

class CsatDashboard extends Component
{
    public int $days = 30;
    public string $view = 'overview'; // overview | agents | ratings

    #[Computed]
    public function team(): Team
    {
        return auth()->user()->currentTeam;
    }

    #[Computed]
    public function stats(): array
    {
        return app(CsatService::class)->getTeamStats($this->team, $this->days);
    }

    #[Computed]
    public function agentStats(): array
    {
        return app(CsatService::class)->getAgentStats($this->team, $this->days);
    }

    #[Computed]
    public function recentRatings()
    {
        return CsatRating::where('team_id', $this->team->id)
            ->with(['contact:id,name,phone_number', 'agent:id,name', 'conversation:id'])
            ->latest()
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.chat.csat-dashboard');
    }
}
