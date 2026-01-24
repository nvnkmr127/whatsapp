<?php

namespace App\Livewire\Automations;

use App\Models\MessageBot;
use App\Models\TemplateBot;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Title('Automations')]
class AutomationList extends Component
{
    use WithPagination;

    public $search = '';
    public $confirmingDeletion = false;
    public $deletionId = null;

    protected $queryString = ['search'];

    public function confirmDelete($id)
    {
        $this->deletionId = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        try {
            \App\Models\Automation::findOrFail($this->deletionId)->delete();
            $this->confirmingDeletion = false;
            $this->deletionId = null;
            $this->dispatch('notify', 'Automation deleted successfully.');
        } catch (\Exception $e) {
            $this->confirmingDeletion = false;
            $this->addError('base', 'Unable to delete automation: ' . $e->getMessage());
        }
    }

    public function duplicate($id)
    {
        try {
            $original = \App\Models\Automation::where('team_id', auth()->user()->currentTeam->id)->findOrFail($id);
            $clone = $original->replicate();
            $clone->name = $original->name . ' (Copy)';
            $clone->is_active = false; // Default to inactive for safety
            $clone->save();

            $this->dispatch('notify', 'Automation duplicated successfully.');
        } catch (\Exception $e) {
            $this->addError('base', 'Unable to duplicate: ' . $e->getMessage());
        }
    }

    public function export($id)
    {
        try {
            $automation = \App\Models\Automation::where('team_id', auth()->user()->currentTeam->id)->findOrFail($id);

            // Format matching the user's example structure or cleaner
            $exportData = [
                'name' => $automation->name,
                'trigger_type' => $automation->trigger_type,
                'trigger_config' => $automation->trigger_config,
                'flow_data' => $automation->flow_data,
                'exported_at' => now()->toIso8601String(),
                'version' => '1.0'
            ];

            return response()->streamDownload(function () use ($exportData) {
                echo json_encode($exportData, JSON_PRETTY_PRINT);
            }, \Illuminate\Support\Str::slug($automation->name) . '-export.json');

        } catch (\Exception $e) {
            $this->addError('base', 'Unable to export: ' . $e->getMessage());
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = \App\Models\Automation::query()
            ->where('team_id', auth()->user()->currentTeam->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        $automations = $query->latest()->paginate(10);

        // Module-Level Core Metrics
        $teamId = auth()->user()->current_team_id;
        $stats = [
            'total' => \App\Models\Automation::where('team_id', $teamId)->count(),
            'active' => \App\Models\Automation::where('team_id', $teamId)->where('is_active', true)->count(),
            'total_runs' => \App\Models\AutomationRun::whereHas('automation', fn($q) => $q->where('team_id', $teamId))->count(),
            'completion_rate' => \App\Models\AutomationRun::whereHas('automation', fn($q) => $q->where('team_id', $teamId))->where('status', 'completed')->count() / max(1, \App\Models\AutomationRun::whereHas('automation', fn($q) => $q->where('team_id', $teamId))->count()) * 100,
        ];

        return view('livewire.automations.automation-list', [
            'automations' => $automations,
            'stats' => $stats
        ]);
    }
}
