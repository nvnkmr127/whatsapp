<?php

namespace App\Livewire\Chat;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Team Inbox')]
class ChatDashboard extends Component
{
    public $activeConversationId = null;

    protected $queryString = ['activeConversationId' => ['except' => '']];

    public function mount()
    {
        $this->validateActiveConversation();
    }

    public function validateActiveConversation()
    {
        if ($this->activeConversationId && \Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->currentTeam) {
            $exists = \App\Models\Conversation::where('team_id', \Illuminate\Support\Facades\Auth::user()->currentTeam->id)
                ->where('id', $this->activeConversationId)
                ->exists();

            if (! $exists) {
                $this->activeConversationId = null;
            }
        }
    }

    // NOTE: no #[On('conversationSelected')] handler here on purpose. The list's
    // #[Modelable] $activeConversationId already syncs the selection to this parent
    // and re-renders it (mounting the message-window). Handling the event here too
    // fired a second render/roundtrip that re-morphed the just-mounted window and
    // left it half-initialized on first open (a full reload had no double-update, so
    // it "worked after reload"). The browser event still drives the mobile-pane JS.

    public function render()
    {
        return view('livewire.chat.chat-dashboard')->layout('components.layouts.app'); // Ensure it uses the main app layout
    }
}
