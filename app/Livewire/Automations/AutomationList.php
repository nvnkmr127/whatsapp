<?php

namespace App\Livewire\Automations;

use App\Models\MessageBot;
use App\Models\TemplateBot;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

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
        \App\Models\Automation::findOrFail($this->deletionId)->delete();
        $this->confirmingDeletion = false;
        $this->deletionId = null;
        $this->dispatch('notify', 'Automation deleted successfully.');
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

        $bots = $query->latest()->paginate(10);

        return view('livewire.automations.automation-list', [
            'bots' => $bots
        ]);
    }
}
