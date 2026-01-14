<?php

namespace App\Livewire\Teams;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class OptInManagement extends Component
{
    public $optInKeywords = [];
    public $optOutKeywords = [];
    public $optInMessage;
    public $optOutMessage;
    public $optInMessageEnabled = false;
    public $optOutMessageEnabled = false;

    // Helper for input binding
    public $newOptInKeyword = '';
    public $newOptOutKeyword = '';

    public function mount()
    {
        $team = Auth::user()->currentTeam;
        $this->optInKeywords = $team->opt_in_keywords ?? [];
        $this->optOutKeywords = $team->opt_out_keywords ?? [];
        $this->optInMessage = $team->opt_in_message;
        $this->optOutMessage = $team->opt_out_message;
        $this->optInMessageEnabled = $team->opt_in_message_enabled;
        $this->optOutMessageEnabled = $team->opt_out_message_enabled;
    }

    public function addOptInKeyword()
    {
        if (trim($this->newOptInKeyword) === '')
            return;
        if (!in_array($this->newOptInKeyword, $this->optInKeywords)) {
            $this->optInKeywords[] = trim($this->newOptInKeyword);
        }
        $this->newOptInKeyword = '';
    }

    public function removeOptInKeyword($index)
    {
        unset($this->optInKeywords[$index]);
        $this->optInKeywords = array_values($this->optInKeywords);
    }

    public function addOptOutKeyword()
    {
        if (trim($this->newOptOutKeyword) === '')
            return;
        if (!in_array($this->newOptOutKeyword, $this->optOutKeywords)) {
            $this->optOutKeywords[] = trim($this->newOptOutKeyword);
        }
        $this->newOptOutKeyword = '';
    }

    public function removeOptOutKeyword($index)
    {
        unset($this->optOutKeywords[$index]);
        $this->optOutKeywords = array_values($this->optOutKeywords);
    }

    public function save()
    {
        $team = Auth::user()->currentTeam;
        $team->forceFill([
            'opt_in_keywords' => $this->optInKeywords,
            'opt_out_keywords' => $this->optOutKeywords,
            'opt_in_message' => $this->optInMessage,
            'opt_out_message' => $this->optOutMessage,
            'opt_in_message_enabled' => $this->optInMessageEnabled,
            'opt_out_message_enabled' => $this->optOutMessageEnabled,
        ])->save();

        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.teams.opt-in-management')->layout('layouts.app');
    }
}
