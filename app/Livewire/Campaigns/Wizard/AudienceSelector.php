<?php

namespace App\Livewire\Campaigns\Wizard;

use Livewire\Component;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Computed;
use App\Models\Contact;
use App\Models\Tag;

class AudienceSelector extends Component
{
    public $selectedTags = [];
    public $selectedContacts = [];
    public $audienceType = 'tags';

    public $contactSearch = '';

    public function updatedSelectedTags()
    {
        $this->dispatch('audienceUpdated', selectedTags: $this->selectedTags);
    }

    public function updatedSelectedContacts()
    {
        $this->dispatch('audienceUpdated', selectedContacts: $this->selectedContacts);
    }

    public function updatedAudienceType()
    {
        $this->dispatch('audienceTypeUpdated', type: $this->audienceType);
    }

    #[Computed]
    public function tags()
    {
        return Tag::where('team_id', auth()->user()->currentTeam->id)->get();
    }

    #[Computed]
    public function contacts()
    {
        $query = Contact::where('team_id', auth()->user()->currentTeam->id);
        if ($this->contactSearch) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->contactSearch}%")
                  ->orWhere('phone_number', 'like', "%{$this->contactSearch}%");
            });
        }
        return $query->latest()->limit(50)->get();
    }

    #[Computed]
    public function audienceCount()
    {
        if ($this->audienceType === 'tags') {
            return Contact::where('team_id', auth()->user()->currentTeam->id)
                ->whereHas('tags', fn($q) => $q->whereIn('tags.id', $this->selectedTags))
                ->count();
        }
        return count($this->selectedContacts);
    }

    public function render()
    {
        return view('livewire.campaigns.wizard.audience-selector');
    }
}
