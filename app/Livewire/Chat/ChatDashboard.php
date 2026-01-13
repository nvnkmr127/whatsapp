<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class ChatDashboard extends Component
{
    public $activeConversationId = null;

    protected $queryString = ['activeConversationId' => ['except' => '']];

    public function render()
    {
        return view('livewire.chat.chat-dashboard')->layout('components.layouts.app'); // Ensure it uses the main app layout
    }
}
