<?php

namespace App\Livewire\Chat;

use Livewire\Attributes\On;
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

    #[On('conversationSelected')]
    public function loadConversation($id)
    {
        $this->activeConversationId = $id;
        $this->validateActiveConversation();
    }

    public function render()
    {
        return view('livewire.chat.chat-dashboard')->layout('components.layouts.app'); // Ensure it uses the main app layout
    }
}
