<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Contact;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;

class GlobalSearch extends Component
{
    public $search = '';
    public $results = [];
    public $isFocused = false;

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {
            $this->results = [];
            return;
        }

        $teamId = Auth::user()->currentTeam->id;

        $contacts = Contact::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->limit(5)
            ->get()
            ->map(function ($contact) {
                return [
                    'type' => 'Contact',
                    'title' => $contact->name ?? $contact->phone,
                    'subtitle' => $contact->phone,
                    'url' => route('contacts.show', $contact->id), // Assuming this route exists
                    'icon' => 'user'
                ];
            });

        $campaigns = Campaign::where('team_id', $teamId)
            ->where('campaign_name', 'like', '%' . $this->search . '%')
            ->limit(3)
            ->get()
            ->map(function ($campaign) {
                return [
                    'type' => 'Campaign',
                    'title' => $campaign->campaign_name,
                    'subtitle' => $campaign->status,
                    'url' => route('campaigns.show', $campaign->id), // Assuming this route exists
                    'icon' => 'speakerphone'
                ];
            });

        $this->results = $contacts->merge($campaigns)->toArray();
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
