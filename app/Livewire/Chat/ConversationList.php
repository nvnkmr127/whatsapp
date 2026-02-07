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
    public $availableCategories = [];

    public function getListeners()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            return [
                "echo-private:teams." . Auth::user()->currentTeam->id . ",.MessageReceived" => '$refresh',
                'chat-messages-read' => '$refresh',
            ];
        }
        return [];
    }

    public function mount()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            $this->availableCategories = \App\Models\Category::where('team_id', Auth::user()->currentTeam->id)
                ->whereIn('target_module', ['chat', 'all'])
                ->where('is_active', true)
                ->get();
        }
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
        if (!Auth::check() || !Auth::user()->currentTeam) {
            return collect();
        }

        return Conversation::query()
            ->with(['contact', 'lastMessage', 'assignee'])
            ->withCount([
                'messages as unread_count' => function ($query) {
                    $query->where('direction', 'inbound')->whereNull('read_at');
                }
            ])
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

    public function getStatsProperty()
    {
        if (!Auth::check() || !Auth::user()->currentTeam) {
            return [
                'active' => 0,
                'unassigned' => 0,
                'sla_breaches' => 0,
                'avg_response' => '0m',
                'resolution' => '0%',
            ];
        }

        $teamId = Auth::user()->currentTeam->id;
        $active = Conversation::where('team_id', $teamId)->where('status', 'open')->count();
        $unassigned = Conversation::where('team_id', $teamId)->where('status', 'open')->whereNull('assigned_to')->count();

        $slaBreaches = Conversation::where('team_id', $teamId)
            ->where('status', 'open')
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->count();

        return [
            'active' => $active,
            'unassigned' => $unassigned,
            'sla_breaches' => $slaBreaches,
            'avg_response' => '14m',
            'resolution' => '92%',
        ];
    }

    public function render()
    {
        return view('livewire.chat.conversation-list', [
            'conversations' => $this->conversations,
            'stats' => $this->stats,
            'availableCategories' => $this->availableCategories,
        ]);
    }
}
