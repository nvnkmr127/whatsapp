<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use App\Services\AutomationAnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Automation Analytics')]
class AutomationAnalytics extends Component
{
    public Automation $automation;
    public array $dashboard = [];
    public string $currencySymbol = '$';

    public function mount(int $automationId, AutomationAnalyticsService $analytics): void
    {
        $teamId = auth()->user()->currentTeam->id;

        $this->automation = Automation::query()
            ->where('team_id', $teamId)
            ->findOrFail($automationId);

        $this->dashboard = $analytics->buildDashboard($this->automation);
        $this->currencySymbol = (string) get_setting('currency_symbol', '$');
    }

    public function refreshData(AutomationAnalyticsService $analytics): void
    {
        $this->dashboard = $analytics->buildDashboard($this->automation->fresh());
        $this->dispatch('notify', 'Automation analytics refreshed.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.automations.automation-analytics');
    }
}
