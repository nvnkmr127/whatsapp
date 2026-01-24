<?php

namespace App\Livewire\Developer;

use App\Models\KnowledgeBaseGap;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeBaseFeedback extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'pending';
    public $resolutionNote = '';
    public $selectedGapId = null;
    public $showResolutionModal = false;

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openResolutionModal($id)
    {
        $gap = KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->selectedGapId = $id;
        $this->resolutionNote = $gap->resolution_note ?? '';
        $this->showResolutionModal = true;
    }

    public function resolveGap()
    {
        $this->validate([
            'resolutionNote' => 'required|string|min:5',
        ]);

        $gap = KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->selectedGapId);
        $gap->update([
            'status' => 'resolved',
            'resolution_note' => $this->resolutionNote,
        ]);

        $this->showResolutionModal = false;
        $this->reset(['selectedGapId', 'resolutionNote']);
        session()->flash('success', 'Gap marked as resolved.');
    }

    public function ignoreGap($id)
    {
        $gap = KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $gap->update(['status' => 'ignored']);
        session()->flash('success', 'Gap ignored.');
    }

    public function render()
    {
        $gaps = KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)
            ->when($this->search, function ($query) {
                $query->where('query', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.developer.knowledge-base-feedback', [
            'gaps' => $gaps
        ]);
    }
}
