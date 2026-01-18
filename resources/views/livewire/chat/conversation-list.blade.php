<div class="flex flex-col h-full bg-transparent">
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
            <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight uppercase">Inbox <span
                    class="text-wa-teal">Center</span></h1>
        </div>
        <div class="flex gap-2">
            <button class="p-2 text-slate-400 hover:text-wa-teal transition-colors hover:bg-wa-teal/10 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Frequency Scanner (Search) -->
    <div class="px-6 py-4 space-y-3" x-data="{ showFilters: false }">
        <div class="flex gap-2">
            <div class="relative group flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search conversations..."
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

        <!-- Filter Panel -->
        <div x-show="showFilters" x-collapse
            class="bg-white dark:bg-slate-950 rounded-2xl p-4 shadow-xl border border-slate-50 dark:border-slate-800 space-y-4 relative z-20">
            <div class="grid grid-cols-1 gap-3">
                <!-- Read Status -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Signal Status</label>
                    <select wire:model.live="filterReadStatus"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-wa-teal/20 py-2">
                        <option value="all">All Signals</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>
                </div>

                <!-- Opt-In -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Consent
                        Protocol</label>
                    <select wire:model.live="filterOptIn"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-wa-teal/20 py-2">
                        <option value="all">Any Status</option>
                        <option value="yes">Subscribed (Opt-In)</option>
                        <option value="no">Unsubscribed</option>
                    </select>
                </div>

                <!-- Blocked -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Transmission
                        Line</label>
                    <select wire:model.live="filterBlocked"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-wa-teal/20 py-2">
                        <option value="all">All Lines</option>
                        <option value="no">Active Only</option>
                        <option value="yes">Blocked/Terminated</option>
                    </select>
                </div>
            </div>

            <button wire:click="resetFilters"
                class="w-full py-2 bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors">
                Reset Protocols
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
        @forelse($conversations as $conversation)
            @php
                $initials = substr($conversation->contact->name ?? '?', 0, 1);
                $isActive = $activeConversationId == $conversation->id;
            @endphp
            <div wire:click="selectConversation({{ $conversation->id }}); mobilePane = 'messages'"
                wire:key="{{ $conversation->id }}"
                class="group flex items-center p-3 rounded-2xl cursor-pointer transition-all duration-200 border border-transparent {{ $isActive ? 'bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/20 border-slate-100 dark:border-slate-700 relative z-10 scale-[1.02]' : 'hover:bg-white/60 dark:hover:bg-slate-800/60 hover:border-slate-100 dark:hover:border-slate-800' }}">

                <!-- Avatar Status -->
                <div class="flex-shrink-0 mr-4 relative">
                    <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $conversation->contact->name ?? 'Unknown' }}"
                        alt="{{ $conversation->contact->name ?? 'Unknown' }}"
                        class="h-12 w-12 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shadow-sm transition-transform duration-300 group-hover:scale-105"
                        loading="lazy">
                    @if($conversation->status === 'open')
                        <div
                            class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-wa-teal border-2 border-white dark:border-slate-800 shadow-sm animate-pulse">
                        </div>
                    @endif
                </div>

                <!-- Channel Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1">
                        <h3
                            class="text-xs font-black {{ $isActive ? 'text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-400' }} truncate tracking-tight uppercase group-hover:text-wa-teal transition-colors">
                            {{ $conversation->contact->name ?? $conversation->contact->phone_number }}
                        </h3>
                        <span class="text-[9px] font-mono font-bold text-slate-400">
                            {{ $conversation->last_message_at ? $conversation->last_message_at->format('H:i') : '' }}
                        </span>
                    </div>
                    <p
                        class="text-[11px] font-medium {{ $isActive ? 'text-slate-500 dark:text-slate-300' : 'text-slate-400' }} truncate">
                        {{ $conversation->lastMessage ? Str::limit($conversation->lastMessage->content ?? '[MEDIA PACKET]', 35) : 'Initialize link...' }}
                    </p>
                </div>

                <!-- Indicators -->
                @if($conversation->assignee || $conversation->status === 'closed')
                    <div class="ml-2 flex flex-col items-end gap-1">
                        @if($conversation->assignee)
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500" title="Assigned"></div>
                        @endif
                        @if($conversation->status === 'closed')
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
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No Signals Intercepted</p>
            </div>
        @endforelse
    </div>
</div>