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

    #[On('conversationSelected')]
    public function loadConversation($id)
    {
        $this->activeConversationId = $id;
    }

    public function render()
    {
        return view('livewire.chat.chat-dashboard')->layout('components.layouts.app'); // Ensure it uses the main app layout
    }
}
