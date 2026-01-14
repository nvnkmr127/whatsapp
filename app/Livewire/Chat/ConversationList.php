<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class ConversationList extends Component
{
    #[Modelable]
    public $activeConversationId;
    public $search = '';
    public $filterReadStatus = 'all'; // all, unread, read
    public $filterOptIn = 'all'; // all, yes, no
    public $filterBlocked = 'all'; // all, yes, no

    protected $listeners = ['echo:conversations,ConversationUpdated' => '$refresh'];

    public function mount()
    {
        // $this->activeConversationId is passed via wire:model from parent
    }

    public function resetFilters()
    {
        $this->filterReadStatus = 'all';
        $this->filterOptIn = 'all';
        $this->filterBlocked = 'all';
        $this->search = '';
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
                        ->orWhere('phone_number', 'like', '%' . $this->search . '%')
                        ->orWhere('custom_attributes', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterReadStatus !== 'all', function ($query) {
                if ($this->filterReadStatus === 'unread') {
                    $query->whereHas('lastMessage', function ($q) {
                        $q->where('direction', 'inbound')->whereNull('read_at');
                    });
                } elseif ($this->filterReadStatus === 'read') {
                    $query->whereHas('lastMessage', function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('direction', 'outbound')
                                ->orWhereNotNull('read_at');
                        });
                    });
                }
            })
            ->when($this->filterOptIn !== 'all', function ($query) {
                $status = $this->filterOptIn === 'yes' ? 'opted_in' : 'opted_out';
                // If filtering for NO, we might also want to include 'none' if that's considered not opted-in, 
                // but usually strictly 'opted_out'. Let's stick to the enum values for now or follow specific logic.
                // If 'no' implies simply NOT opted_in, it could be 'none' or 'opted_out'. 
                // Given the requirement "Opted In: Yes/No", usually "No" means explicitly not opted in.
                // Let's assume binary for now based on 'opted_in' vs others if simpler, but let's try strict first.
                // Actually, let's map 'yes' -> 'opted_in', 'no' -> ['none', 'opted_out']?
                // Let's stick to strict 'opted_in' vs 'opted_out' for now unless 'none' is common.
                // Re-reading migration: default 'none'.
                // So 'no' probably acts as "Not Opted In" which is 'none' OR 'opted_out'.
                if ($this->filterOptIn === 'yes') {
                    $query->whereHas('contact', fn($q) => $q->where('opt_in_status', 'opted_in'));
                } else {
                    $query->whereHas('contact', fn($q) => $q->whereIn('opt_in_status', ['none', 'opted_out']));
                }
            })
            ->when($this->filterBlocked !== 'all', function ($query) {
                if ($this->filterBlocked === 'yes') {
                    $query->where('status', 'blocked');
                } else {
                    $query->where('status', '!=', 'blocked');
                }
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
