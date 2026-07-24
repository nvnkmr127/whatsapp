<div class="flex flex-col h-full bg-transparent"
    x-data="inboxLive('{{ auth()->user()?->currentTeam?->id }}')">
    <!-- Header -->
    <!-- Header -->
    <div class="px-6 py-6 border-b border-slate-50 dark:border-slate-800 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="p-1.5 bg-wa-teal/10 text-wa-teal rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ __('Inbox') }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex flex-col items-end border-r border-slate-100 dark:border-slate-800 pr-3 mr-1">
                <span class="text-[8px] font-black uppercase text-slate-400 leading-none mb-0.5">{{ __('Active') }}</span>
                <span class="text-xs font-black text-wa-teal leading-none">{{ $stats['active'] }}</span>
            </div>
            <div class="hidden sm:flex flex-col items-end border-r border-slate-100 dark:border-slate-800 pr-3 mr-1">
                <span class="text-[8px] font-black uppercase text-slate-400 leading-none mb-0.5">{{ __('Response') }}</span>
                <span
                    class="text-xs font-black text-slate-800 dark:text-white leading-none">{{ $stats['avg_response'] }}</span>
            </div>
            <div class="hidden sm:flex flex-col items-end">
                <span class="text-[8px] font-black uppercase text-slate-400 leading-none mb-0.5">{{ __('Pending') }}</span>
                <span
                    class="text-xs font-black {{ $stats['unassigned'] > $pendingWarningLimit ? 'text-rose-500' : 'text-slate-800 dark:text-white' }} leading-none">{{ $stats['unassigned'] }}</span>
            </div>
        </div>
    </div>

    <!-- Frequency Scanner (Search) -->
    <div class="px-6 py-4 space-y-3" x-data="{ showFilters: false }">
        <div class="flex gap-2">
            <div class="relative group flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search...') }}"
                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-400 transition-all shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <button @click="showFilters = !showFilters"
                :class="showFilters ? 'bg-wa-teal text-white shadow-wa-teal/20' : 'bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-wa-teal'"
                class="p-3 rounded-xl shadow-sm transition-all hover:shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </button>
        </div>

        @if(mb_strlen($search) > 0 && mb_strlen($search) < 3)
            <p class="px-1 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                {{ __('Searching names and numbers — type 3 characters to include message text') }}
            </p>
        @endif

        <!-- Filter Panel -->
        <div x-show="showFilters" x-collapse
            class="bg-white dark:bg-slate-950 rounded-2xl p-4 shadow-xl border border-slate-50 dark:border-slate-800 space-y-4 relative z-20">
            <div class="grid grid-cols-1 gap-3">
                <!-- Read Status -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Read Status') }}</label>
                    <select wire:model.live="filterReadStatus"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-wa-teal/20 py-2">
                        <option value="all">{{ __('All') }}</option>
                        <option value="unread">{{ __('Unread') }}</option>
                        <option value="read">{{ __('Read') }}</option>
                    </select>
                </div>

                <!-- Opt-In -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Subscription Status') }}</label>
                    <select wire:model.live="filterOptIn"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-wa-teal/20 py-2">
                        <option value="all">{{ __('Any') }}</option>
                        <option value="yes">{{ __('Subscribed') }}</option>
                        <option value="no">{{ __('Unsubscribed') }}</option>
                    </select>
                </div>

                <!-- Blocked -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ __('Contact Status') }}</label>
                    <select wire:model.live="filterBlocked"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-wa-teal/20 py-2">
                        <option value="all">{{ __('All') }}</option>
                        <option value="no">{{ __('Active') }}</option>
                        <option value="yes">{{ __('Blocked') }}</option>
                    </select>
                </div>
            </div>

            <button wire:click="resetFilters"
                class="w-full py-2 bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors">
                {{ __('Clear Filters') }}
            </button>
        </div>
    </div>


    <!-- Channel List with Enhanced Scrolling -->
    <div class="flex-1 overflow-y-auto space-y-2 px-4 pb-4 scroll-smooth"
        style="max-height: calc(100vh - 280px); scrollbar-width: thin; scrollbar-color: rgb(20 184 166 / 0.3) transparent;">
        <style>
            /* Custom Scrollbar for Webkit Browsers */
            .flex-1.overflow-y-auto::-webkit-scrollbar {
                width: 6px;
            }

            .flex-1.overflow-y-auto::-webkit-scrollbar-track {
                background: transparent;
                border-radius: 10px;
            }

            .flex-1.overflow-y-auto::-webkit-scrollbar-thumb {
                background: rgb(20 184 166 / 0.3);
                border-radius: 10px;
                transition: background 0.2s;
            }

            .flex-1.overflow-y-auto::-webkit-scrollbar-thumb:hover {
                background: rgb(20 184 166 / 0.5);
            }
        </style>
        @forelse($conversations as $convItem)
            @php
                $convId = data_get($convItem, 'id');
                $contactName = data_get($convItem, 'contact.name');
                $contactPhone = data_get($convItem, 'contact.phone_number');
                $displayName = $contactName ?: ($contactPhone ?: 'Unknown');
                $initials = substr($displayName, 0, 1);
                $isActive = $activeConversationId == $convId;
                $slaDue = data_get($convItem, 'sla_due_at');
                $slaDueCarbon = $slaDue ? ($slaDue instanceof \Carbon\Carbon ? $slaDue : \Carbon\Carbon::parse($slaDue)) : null;
                $status = data_get($convItem, 'status');
                $isSlaBreached = $slaDueCarbon && $slaDueCarbon->isPast() && $status !== 'closed';
                $isSlaWarning = $slaDueCarbon && $slaDueCarbon->diffInMinutes(now()) < $slaWarningMinutes && !$isSlaBreached && $status !== 'closed';
                $tags = data_get($convItem, 'metadata.tags', []);
                $hasActiveCall = data_get($convItem, 'has_active_call') || data_get($convItem, 'hasActiveCall');
                $unreadCount = data_get($convItem, 'unread_count', 0);
                $lastMsgAt = data_get($convItem, 'last_message_at');
                $lastMsgContent = data_get($convItem, 'lastMessage.content') ?: data_get($convItem, 'last_message.content');
                $assignee = data_get($convItem, 'assignee') || data_get($convItem, 'assigned_to');
            @endphp
            <div wire:click="selectConversation({{ $convId }})"
                wire:key="{{ $convId }}"
                class="group flex items-center p-3 rounded-2xl cursor-pointer transition-all duration-200 border border-transparent 
                        {{ $isActive ? 'bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/20 border-slate-100 dark:border-slate-700 relative z-10 scale-[1.02]' : 'hover:bg-white/60 dark:hover:bg-slate-800/60 hover:border-slate-100 dark:hover:border-slate-800' }}
                        {{ $isSlaBreached ? 'ring-2 ring-rose-500/50 bg-rose-50/10' : ($isSlaWarning ? 'ring-2 ring-amber-500/30 bg-amber-50/5' : '') }}">

                <!-- Avatar Status -->
                <div class="flex-shrink-0 mr-4 relative">
                    <div class="relative">
                        @if($hasActiveCall)
                            <div class="absolute -inset-1.5 bg-emerald-500/30 rounded-full animate-ping"></div>
                        @endif
                        <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $displayName }}"
                            alt="{{ $displayName }}"
                            class="relative h-12 w-12 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shadow-sm transition-transform duration-300 group-hover:scale-105"
                            loading="lazy">
                    </div>
                    @if($unreadCount > 0)
                        <div
                            class="absolute -top-1 -right-1 h-5 min-w-[20px] px-1.5 flex items-center justify-center rounded-full bg-wa-teal border-2 border-white dark:border-slate-800 shadow-sm animate-pulse">
                            <span class="text-[9px] font-black text-white leading-none">{{ $unreadCount }}</span>
                        </div>
                    @endif
                </div>

                <!-- Channel Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                        <h3
                            class="text-xs font-black {{ $isActive ? 'text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-400' }} truncate tracking-tight uppercase group-hover:text-wa-teal transition-colors">
                            {{ $displayName }}
                        </h3>
                        <div class="flex flex-col items-end">
                            <span class="text-[9px] font-mono font-bold text-slate-400">
                                {{ $this->formatTime($lastMsgAt ? ($lastMsgAt instanceof \Carbon\Carbon ? $lastMsgAt : \Carbon\Carbon::parse($lastMsgAt)) : null) }}
                            </span>
                            @if($slaDueCarbon && $status !== 'closed')
                                <div x-data="{ 
                                    dueAt: '{{ $slaDueCarbon->toIso8601String() }}',
                                    countdown: '',
                                    update() {
                                        let diff = new Date(this.dueAt) - new Date();
                                        if (diff < 0) { this.countdown = 'BREACH'; return; }
                                        let h = Math.floor(diff / 3600000);
                                        let m = Math.floor((diff % 3600000) / 60000);
                                        this.countdown = (h > 0 ? h + 'h ' : '') + m + 'm';
                                    },
                                    init() { 
                                        this.update(); 
                                        let timer = setInterval(() => this.update(), 60000); 
                                        this.$cleanup(() => clearInterval(timer));
                                    }
                                }" class="mt-0.5">
                                    <span :class="countdown === 'BREACH' ? 'text-rose-500' : 'text-amber-500'" 
                                          class="text-[8px] font-black uppercase tracking-tighter" x-text="countdown"></span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <p class="text-tiny font-bold text-slate-500 dark:text-slate-400 truncate pr-4 leading-relaxed group-hover:text-slate-600 transition-colors">
                    {{ $lastMsgContent ? Str::limit($lastMsgContent, 35) : __('Photo/Video') }}
                    </p>
                    @if(!empty($tags))
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($tags as $tagId)
                                @php $category = collect($availableCategories)->firstWhere('id', $tagId); @endphp
                                @if($category)
                                    <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider"
                                        style="background-color: {{ $category->color }}20; color: {{ $category->color }}">
                                        {{ $category->name }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Indicators -->
                @if($assignee || $status === 'closed')
                    <div class="ml-2 flex flex-col items-end gap-1">
                        @if($assignee)
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500" title="Assigned"></div>
                        @endif
                        @if($status === 'closed')
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300" title="Closed"></div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div
                class="p-8 text-center bg-slate-100/50 dark:bg-slate-800/30 rounded-[2rem] m-4 border border-dashed border-slate-200 dark:border-slate-700">
                <div class="text-slate-300 dark:text-slate-600 mb-3 flex justify-center">
                    <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ __('No conversations found') }}</p>
            </div>
        @endforelse

        @if(count($conversations) >= $perPage)
            <div x-intersect="$wire.loadMore()" class="flex justify-center p-4">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-wa-teal"></div>
            </div>
        @endif
    </div>
</div>