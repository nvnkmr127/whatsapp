<?php

namespace App\Livewire\Contacts;

use App\Models\ContactTag;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TagManager extends Component
{
    public $isOpen = false;

    public $newTagName = '';

    public $newTagColor = '#10B981';

    protected $listeners = ['openTagManager' => 'open'];

    public function open()
    {
        $this->isOpen = true;
        $this->resetInput();
    }

    public function close()
    {
        $this->isOpen = false;
        $this->dispatch('tags-updated'); // Tell parent to refresh tags if needed
    }

    public function createTag()
    {
        $this->validate([
            'newTagName' => 'required|string|max:50',
            'newTagColor' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        ContactTag::create([
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->newTagName,
            'color' => $this->newTagColor,
        ]);

        $this->resetInput();
        session()->flash('message', 'Tag created successfully.');
    }

    public function deleteTag($id)
    {
        $tag = ContactTag::where('team_id', Auth::user()->currentTeam->id)->find($id);
        if ($tag) {
            $tag->delete();
            session()->flash('message', 'Tag deleted successfully.');
        }
    }

    private function resetInput()
    {
        $this->newTagName = '';
        $this->newTagColor = '#10B981';
    }

    public function render()
    {
        $tags = ContactTag::where('team_id', Auth::user()->currentTeam->id)->get();

        return view('livewire.contacts.tag-manager', compact('tags'));
    }
}
