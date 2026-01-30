<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatBeacon extends Component
{
    public $unreadCount = 0;
    public $latestMessage = null;
    public $isOpen = false;
    public $activeConversationId = null;

    public function getListeners()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $teamId = Auth::user()->currentTeam->id;
            return [
                "echo-private:teams.{$teamId},.MessageReceived" => 'handleNewMessage',
                "echo-private:teams.{$teamId},.ConversationAssigned" => 'handleAssignment',
                'chat-messages-read' => 'refreshStats',
            ];
        }
        return [];
    }

    public function mount()
    {
        $this->refreshStats();
    }

    public function refreshStats()
    {
        if (!Auth::check() || !Auth::user()->currentTeam)
            return;

        $teamId = Auth::user()->currentTeam->id;

        $this->unreadCount = \App\Models\Message::where('team_id', $teamId)
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->count();

        $this->latestMessage = \App\Models\Message::where('team_id', $teamId)
            ->where('direction', 'inbound')
            ->latest()
            ->first();
    }

    public function handleNewMessage($event)
    {
        $this->refreshStats();
        $this->dispatch('play-sound');
    }

    public function handleAssignment($event)
    {
        if ($event['assigned_to'] == Auth::id()) {
            $this->refreshStats();
            $this->dispatch('play-sound');
            $this->dispatch('notify', [
                'type' => 'info',
                'title' => 'New Chat Assigned',
                'message' => "{$event['assigned_by_name']} transferred a chat with {$event['contact_name']} to you."
            ]);
        }
    }

    public function toggleInbox()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->refreshStats();
        }
    }

    public function openConversation($id)
    {
        $this->activeConversationId = $id;
    }

    public function render()
    {
        return view('livewire.chat.chat-beacon');
    }
}
