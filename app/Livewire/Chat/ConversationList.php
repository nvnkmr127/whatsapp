<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class ConversationList extends Component
{
    /** Below this, searching message bodies scans everything to match everything. */
    private const MESSAGE_SEARCH_MIN_LENGTH = 3;

    #[Modelable]
    public $activeConversationId;

    public $search = '';

    public $filterReadStatus = 'all'; // all, unread, read

    public $filterOptIn = 'all'; // all, yes, no

    public $filterBlocked = 'all'; // all, yes, no

    public $perPage = 25;

    public $availableCategories = [];

    // Thresholds
    public $slaWarningMinutes = 60;

    public $pendingWarningLimit = 5;

    public function getListeners()
    {
        if (Auth::check() && Auth::user()->currentTeam) {
            // MessageReceived is handled by the inboxLive Alpine component instead —
            // a raw echo listener here rebuilt the whole inbox once per message.
            return [
                'chat-messages-read' => '$refresh',
                'refresh-tags' => '$refresh',
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

    public function formatTime($time)
    {
        if (! $time) {
            return '';
        }

        $now = now();

        if ($time->diffInHours($now) < 24) {
            return $time->diffForHumans(['parts' => 1, 'short' => true]);
        }

        if ($time->isYesterday()) {
            return __('Yesterday');
        }

        return $time->format('M d');
    }

    public function resetFilters()
    {
        $this->filterReadStatus = 'all';
        $this->filterOptIn = 'all';
        $this->filterBlocked = 'all';
        $this->search = '';
        $this->perPage = 25;
    }

    public function loadMore()
    {
        $this->perPage += 25;
    }

    public function updatedSearch()
    {
        $this->perPage = 25;
    }

    public function updatedFilterReadStatus()
    {
        $this->perPage = 25;
    }

    public function updatedFilterOptIn()
    {
        $this->perPage = 25;
    }

    public function updatedFilterBlocked()
    {
        $this->perPage = 25;
    }

    public function selectConversation($id)
    {
        $this->activeConversationId = $id;
        $this->dispatch('conversationSelected', $id);
    }

    public function getConversationsProperty()
    {
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            return collect();
        }

        $query = Conversation::query()
            ->with(['contact', 'lastMessage', 'assignee'])
            ->withCount([
                'messages as unread_count' => function ($query) {
                    $query->where('direction', 'inbound')->whereNull('read_at');
                },
            ])
            ->withExists([
                'calls as has_active_call' => function ($query) {
                    $query->whereIn('status', ['initiated', 'ringing', 'in_progress']);
                },
            ])
            ->where('team_id', Auth::user()->currentTeam->id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('contact', function ($sub) {
                        $sub->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('phone_number', 'like', '%'.$this->search.'%')
                            ->orWhere('custom_attributes', 'like', '%'.$this->search.'%');
                    });

                    // One or two characters match almost everything, so searching
                    // bodies for them costs a scan to return noise. The floor also
                    // matches InnoDB's default innodb_ft_min_token_size of 3.
                    if (mb_strlen($this->search) >= self::MESSAGE_SEARCH_MIN_LENGTH) {
                        $q->orWhereHas('messages', function ($sub) {
                            $this->applyContentSearch($sub);
                        });
                    }
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
                    $query->whereHas('contact', fn ($q) => $q->where('opt_in_status', 'opted_in'));
                } else {
                    $query->whereHas('contact', fn ($q) => $q->whereIn('opt_in_status', ['none', 'opted_out']));
                }
            })
            ->when($this->filterBlocked !== 'all', function ($query) {
                if ($this->filterBlocked === 'yes') {
                    $query->where('status', 'blocked');
                } else {
                    $query->where('status', '!=', 'blocked');
                }
            })
            ->orderByDesc('last_message_at');

        $conversations = $query->take($this->perPage)->get();

        if ($this->activeConversationId && ! $conversations->contains('id', (int) $this->activeConversationId)) {
            $activeConv = Conversation::query()
                ->with(['contact', 'lastMessage', 'assignee'])
                ->withCount([
                    'messages as unread_count' => function ($q) {
                        $q->where('direction', 'inbound')->whereNull('read_at');
                    },
                ])
                ->withExists([
                    'calls as has_active_call' => function ($q) {
                        $q->whereIn('status', ['initiated', 'ringing', 'in_progress']);
                    },
                ])
                ->where('team_id', Auth::user()->currentTeam->id)
                ->find($this->activeConversationId);

            if ($activeConv) {
                $conversations->prepend($activeConv);
            }
        }

        return $conversations;
    }

    /**
     * Match message bodies against the FULLTEXT index where the driver has one,
     * and fall back to LIKE where it doesn't (SQLite in tests).
     */
    private function applyContentSearch($query): void
    {
        $term = $this->usesFullTextSearch() ? $this->booleanModeTerm($this->search) : '';

        if ($term === '') {
            $query->where('content', 'like', '%'.$this->search.'%');

            return;
        }

        $query->whereFullText('content', $term, ['mode' => 'boolean']);
    }

    private function usesFullTextSearch(): bool
    {
        // Postgres would need a different index and expression; the app runs MySQL,
        // so anything else takes the LIKE path rather than an untested one.
        return in_array(\Illuminate\Support\Facades\DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Keep only letters/digits/underscore, which is roughly how InnoDB tokenises
     * text — and conveniently means no boolean-mode operator (+ - > < ( ) ~ * " @)
     * can survive to invert a match or break the query. Each surviving word
     * becomes +word*:
     *
     *   *  trailing wildcard, so partial words still hit the way LIKE '%term%' did
     *   +  required, because boolean mode ORs terms by default — without it,
     *      typing more words would widen the result set instead of narrowing it
     *
     * Words below innodb_ft_min_token_size are dropped rather than required: they
     * are not in the index at all, so "+re*" from "re-order" could never match.
     *
     * Returns '' when nothing usable survives, which sends the caller back to LIKE.
     */
    private function booleanModeTerm(string $search): string
    {
        preg_match_all('/[\p{L}\p{N}_]+/u', $search, $matches);

        $words = array_filter(
            $matches[0],
            fn ($word) => mb_strlen($word) >= self::MESSAGE_SEARCH_MIN_LENGTH
        );

        if (empty($words)) {
            return '';
        }

        return implode(' ', array_map(fn ($word) => '+'.$word.'*', $words));
    }

    public function getStatsProperty()
    {
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            return [
                'active' => 0, 'unassigned' => 0, 'sla_breaches' => 0,
                'avg_response' => '-', 'resolution' => '-',
            ];
        }

        $teamId = Auth::user()->currentTeam->id;

        return cache()->remember("chat_stats_{$teamId}", 30, function () use ($teamId) {
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
                'avg_response' => '-',
                'resolution' => '-',
            ];
        });
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
