<div class="flex flex-col h-full bg-transparent">
    <!-- Header -->
    <div class="px-6 py-6 border-b border-slate-100 dark:border-slate-800/50 flex justify-between items-center">
        <div>
            <h2 class="font-black text-xs text-slate-400 uppercase tracking-[0.2em] mb-1">Comms Uplink</h2>
            <h1 class="font-black text-xl text-slate-900 dark:text-white uppercase tracking-tight">Active Channels</h1>
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
    <div class="px-6 py-4">
        <div class="relative group">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Scan frequencies..."
                class="w-full pl-10 pr-4 py-3 bg-white dark:bg-slate-800 border-none rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-400 transition-all shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400 group-focus-within:text-wa-teal transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Channel List -->
    <div class="flex-1 overflow-y-auto custom-scrollbar space-y-2 px-4 pb-4">
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
                    <div
                        class="h-12 w-12 rounded-xl {{ $isActive ? 'bg-wa-teal text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }} flex items-center justify-center font-black text-lg shadow-sm transition-colors duration-300">
                        {{ $initials }}
                    </div>
                    @if($conversation->status === 'open')
                        <div
                            class="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-wa-green border-2 border-white dark:border-slate-800 shadow-sm animate-pulse">
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