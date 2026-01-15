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
    use WithFileUploads;

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
        ]);

        $this->loadSources();
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

        $service = new KnowledgeBaseService();
        $content = $service->extractFromFile($path, $this->file->getClientOriginalName());

        KnowledgeBaseSource::create([
            'team_id' => Auth::user()->currentTeam->id,
            'type' => 'file',
            'name' => $this->name,
            'path' => $path,
            'content' => $content,
            'metadata' => [
                'original_name' => $this->file->getClientOriginalName(),
                'extension' => $this->file->extension(),
            ]
        ]);

        $this->reset(['file', 'name']);
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'Information added successfully.');
    }

    public function addUrl()
    {
        $this->validate([
            'url' => 'required|url',
            'name' => 'required|string|max:255',
        ]);

        $service = new KnowledgeBaseService();
        $content = $service->extractFromUrl($this->url);

        KnowledgeBaseSource::create([
            'team_id' => Auth::user()->currentTeam->id,
            'type' => 'url',
            'name' => $this->name,
            'path' => $this->url,
            'content' => $content,
            'metadata' => [
                'url' => $this->url,
            ]
        ]);

        $this->reset(['url', 'name']);
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'Website information added.');
    }

    public function addText()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'rawText' => 'required|string',
        ]);

        KnowledgeBaseSource::create([
            'team_id' => Auth::user()->currentTeam->id,
            'type' => 'text',
            'name' => $this->name,
            'path' => null,
            'content' => $this->rawText,
            'metadata' => []
        ]);

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
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'Information removed.');
    }

    public function reprocessSource($id)
    {
        $source = KnowledgeBaseSource::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $service = new KnowledgeBaseService();
        $content = '';

        if ($source->type === 'file' && $source->path) {
            $content = $service->extractFromFile($source->path, $source->metadata['original_name'] ?? 'file');
        } elseif ($source->type === 'url' && $source->path) {
            $content = $service->extractFromUrl($source->path);
        } else {
            return;
        }

        $source->update(['content' => $content]);
        $this->loadSources();
        $this->dispatch('saved');
        session()->flash('success', 'Information refreshed and relearned.');
    }

    public function render()
    {
        return view('livewire.developer.knowledge-base-manager');
    }
}