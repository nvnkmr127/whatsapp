<?php

namespace App\Livewire\Analytics;

use Livewire\Component;
use Livewire\Attributes\Layout;

class Dashboard extends Component
{
    public $dateRange = 30; // days
    public $stats = [];
    public $chartData = [];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $teamId = auth()->user()->currentTeam->id;
        $startDate = now()->subDays($this->dateRange);

        // 1. Totals
        $this->stats['sent'] = \App\Models\Message::where('team_id', $teamId)
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $startDate)
            ->count();

        $this->stats['received'] = \App\Models\Message::where('team_id', $teamId)
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $startDate)
            ->count();

        $this->stats['conversations'] = \App\Models\Conversation::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->count();

        // 2. Chart Data: Daily Volume (Sent vs Received)
        // Group by Date(created_at).
        // SQLite/MySQL specific format? I'll use a collection loop to be safe and DB agnostic-ish or `selectRaw`.
        // Using Eloquent Collection for MVP simplicity if volume isn't huge. Or `selectRaw`.

        $raw = \App\Models\Message::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, direction, count(*) as count')
            ->groupBy('date', 'direction')
            ->orderBy('date')
            ->get();

        $dates = [];
        for ($i = $this->dateRange - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $dates[$d] = ['inbound' => 0, 'outbound' => 0];
        }

        foreach ($raw as $r) {
            $d = $r->date; // Depends on DB driver if it returns string or Obj
            // Accessor might convert. Let's assume string Y-m-d.
            if (isset($dates[$d])) {
                $dates[$d][$r->direction] = $r->count;
            }
        }

        $this->chartData = [
            'labels' => array_keys($dates),
            'datasets' => [
                [
                    'label' => 'Sent',
                    'data' => array_column($dates, 'outbound'),
                    'borderColor' => '#3b82f6', // Tailwind blue-500
                    'backgroundColor' => '#93c5fd',
                ],
                [
                    'label' => 'Received',
                    'data' => array_column($dates, 'inbound'),
                    'borderColor' => '#10b981', // Tailwind green-500
                    'backgroundColor' => '#6ee7b7',
                ]
            ]
        ];
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.analytics.dashboard');
    }
}
