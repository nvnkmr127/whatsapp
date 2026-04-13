<?php

namespace App\Livewire\Campaigns\Wizard;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Contact;
use App\Models\ContactTag;
use Illuminate\Support\Facades\Auth;

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

    public function goToStep(int $step): void
    {
        $this->dispatch('campaignWizardGoToStep', step: $step);
    }

    #[Computed]
    public function tags()
    {
        return ContactTag::where('team_id', Auth::user()->currentTeam->id)->get();
    }

    #[Computed]
    public function contacts()
    {
        $query = Contact::where('team_id', Auth::user()->currentTeam->id);
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
        $base = Contact::where('team_id', Auth::user()->currentTeam->id)
            ->where('opt_in_status', 'opted_in');

        if ($this->audienceType === 'tags') {
            return $base
                ->whereHas('tags', fn ($q) => $q->whereIn('contact_tags.id', $this->selectedTags))
                ->count();
        }
        if ($this->audienceType === 'contacts') {
            return $base->whereIn('id', $this->selectedContacts)->count();
        }

        return $base->count();
    }

    public function render()
    {
        return view('livewire.campaigns.wizard.audience-selector');
    }
}
