<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ConversationList extends Component
{
    public $activeConversationId;
    public $search = '';

    protected $listeners = ['echo:conversations,ConversationUpdated' => '$refresh'];

    public function mount()
    {
        // $this->activeConversationId is passed via wire:model from parent
    }

    public function selectConversation($id)
    {
        $this->activeConversationId = $id;
        $this->dispatch('conversationSelected', $id);
    }

    public function getConversationsProperty()
    {
        return Conversation::query()
            ->with(['contact', 'lastMessage', 'assignee'])
            ->where('team_id', Auth::user()->currentTeam->id)
            ->when($this->search, function ($query) {
                $query->whereHas('contact', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone_number', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('last_message_at')
            ->take(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.chat.conversation-list', [
            'conversations' => $this->conversations
        ]);
    }
}
