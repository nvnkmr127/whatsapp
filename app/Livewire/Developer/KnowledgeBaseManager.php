<?php

namespace App\Livewire\Developer;

use App\Models\KnowledgeBaseSource;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class KnowledgeBaseManager extends Component
{
    use WithFileUploads, \Livewire\WithPagination;

    public $showFeedback = false;
    public $searchFeedback = '';
    public $statusFilter = 'pending';
    public $resolutionNote = '';
    public $selectedGapId = null;
    public $showResolutionModal = false;

    public $sources = [];
    public $file;
    public $url;
    public $name;
    public $rawText;

    // Preview & Edit
    public $editingId;
    public $editingName;
    public $editingContent;
    public $editingType;
    public $showModal = false;
    public $modalMode = 'preview'; // preview or edit

    public function mount()
    {
        $this->loadSources();
    }

    public function loadSources()
    {
        $this->sources = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function showPreview($id)
    {
        $source = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->editingId = $id;
        $this->editingName = $source->name;
        $this->editingContent = $source->content;
        $this->editingType = $source->type;
        $this->modalMode = 'preview';
        $this->showModal = true;
    }

    public function editSource($id)
    {
        $source = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);

        // Prevent editing for URL sources
        if ($source->type === 'url') {
            return;
        }

        $this->editingId = $id;
        $this->editingName = $source->name;
        $this->editingContent = $source->content;
        $this->editingType = $source->type;
        $this->modalMode = 'edit';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['editingId', 'editingName', 'editingContent', 'editingType']);
    }

    public function saveEdit()
    {
        $this->validate([
            'editingName' => 'required|string|max:255',
            'editingContent' => 'required|string',
        ]);

        $source = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->editingId);
        $source->update([
            'name' => $this->editingName,
            'content' => $this->editingContent,
            'last_synced_at' => now(), // Manual edit counts as sync
        ]);

        $this->loadSources();
        audit('knowledge_base.updated', "Updated knowledge base source '{$source->name}'", $source);
        $this->closeModal();
        $this->dispatch('saved');
        session()->flash('success', 'Information updated.');
    }

    public function uploadFile()
    {
        $this->validate([
            'file' => 'required|max:10240|mimes:pdf,txt',
            'name' => 'required|string|max:255',
        ]);

        $path = $this->file->store('knowledge_base', 'local');

        $source = KnowledgeBaseSource::create([
            'team_id' => Auth::user()->currentTeam->id,
            'type' => 'file',
            'name' => $this->name,
            'path' => $path,
            'content' => '', // Content will be populated by job
            'status' => KnowledgeBaseSource::STATUS_PENDING,
            'metadata' => [
                'original_name' => $this->file->getClientOriginalName(),
                'extension' => $this->file->extension(),
            ]
        ]);

        \App\Jobs\ProcessKnowledgeBaseSourceJob::dispatch($source);

        audit('knowledge_base.added', "Added knowledge base source '{$this->name}' (File)", $source);

        $this->reset(['file', 'name']);
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'File uploaded. Processing started in background.');
    }

    public function addUrl()
    {
        $this->validate([
            'url' => 'required|url',
            'name' => 'required|string|max:255',
        ]);

        $source = KnowledgeBaseSource::create([
            'team_id' => Auth::user()->currentTeam->id,
            'type' => 'url',
            'name' => $this->name,
            'path' => $this->url,
            'content' => '', // Will be filled by job
            'status' => KnowledgeBaseSource::STATUS_PENDING,
            'metadata' => [
                'url' => $this->url,
            ]
        ]);

        \App\Jobs\ProcessKnowledgeBaseSourceJob::dispatch($source);

        audit('knowledge_base.added', "Added knowledge base source '{$this->name}' (URL)", $source);

        $this->reset(['url', 'name']);
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'URL added. Crawling started in background.');
    }

    public function addText()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'rawText' => 'required|string',
        ]);

        $source = KnowledgeBaseSource::create([
            'team_id' => Auth::user()->currentTeam->id,
            'type' => 'text',
            'name' => $this->name,
            'path' => null,
            'content' => $this->rawText,
            'status' => KnowledgeBaseSource::STATUS_READY, // Text is instant
            'last_synced_at' => now(),
            'metadata' => []
        ]);

        audit('knowledge_base.added', "Added knowledge base source '{$this->name}' (Text)", $source);

        $this->reset(['rawText', 'name']);
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'General information added.');
    }

    public function deleteSource($id)
    {
        $source = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);

        if ($source->type === 'file' && $source->path) {
            Storage::disk('local')->delete($source->path);
        }

        $source->delete();
        audit('knowledge_base.deleted', "Deleted knowledge base source '{$source->name}'");
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'Information removed.');
    }

    public function reprocessSource($id)
    {
        $source = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);

        $source->update([
            'status' => KnowledgeBaseSource::STATUS_PENDING,
            'error_message' => null,
        ]);

        \App\Jobs\ProcessKnowledgeBaseSourceJob::dispatch($source);

        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'Reprocessing started.');
    }

    public function toggleFeedback()
    {
        $this->showFeedback = !$this->showFeedback;
        $this->resetPage();
    }

    public function openResolutionModal($id)
    {
        $gap = \App\Models\KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->selectedGapId = $id;
        $this->resolutionNote = $gap->resolution_note ?? '';
        $this->showResolutionModal = true;
    }

    public function resolveGap()
    {
        $this->validate([
            'resolutionNote' => 'required|string|min:5',
        ]);

        $gap = \App\Models\KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->selectedGapId);
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
        $gap = \App\Models\KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $gap->update(['status' => 'ignored']);
        session()->flash('success', 'Gap ignored.');
    }

    public function render()
    {
        $gaps = [];
        if ($this->showFeedback) {
            $gaps = \App\Models\KnowledgeBaseGap::where('team_id', Auth::user()->currentTeam->id)
                ->when($this->searchFeedback, function ($query) {
                    $query->where('query', 'like', '%' . $this->searchFeedback . '%');
                })
                ->when($this->statusFilter, function ($query) {
                    $query->where('status', $this->statusFilter);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('livewire.developer.knowledge-base-manager', [
            'gaps' => $gaps
        ]);
    }
}