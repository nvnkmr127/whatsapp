<div class="flex flex-col h-full bg-slate-50 dark:bg-slate-900/50">
    <!-- Header -->
    <div
        class="px-6 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900">
        <h2 class="font-bold text-xl text-slate-800 dark:text-slate-100 uppercase tracking-tighter">Chats</h2>
        <div class="flex gap-2">
            <button class="p-2 text-slate-400 hover:text-wa-green transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="px-4 py-4">
        <div class="relative group">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search customer..."
                class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-wa-green/20 focus:border-wa-green transition-all shadow-sm group-hover:shadow-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="flex-1 overflow-y-auto space-y-1 px-2 pb-4">
        @forelse($conversations as $conversation)
            @php
                $initials = substr($conversation->contact->name ?? '?', 0, 1);
                $bgColors = ['bg-orange-500', 'bg-blue-500', 'bg-purple-500', 'bg-teal-500', 'bg-pink-500'];
                $bgColor = $bgColors[ord($initials) % count($bgColors)];
            @endphp
            <div wire:click="selectConversation({{ $conversation->id }}); mobilePane = 'messages'"
                class="flex items-center p-3 rounded-2xl cursor-pointer transition-all duration-200 group {{ $activeConversationId == $conversation->id ? 'bg-white dark:bg-slate-800 shadow-md ring-1 ring-slate-200 dark:ring-slate-700' : 'hover:bg-slate-200/50 dark:hover:bg-slate-800/30' }}">

                <!-- Avatar -->
                <div class="flex-shrink-0 mr-4 relative">
                    <div
                        class="h-14 w-14 rounded-2xl {{ $bgColor }} flex items-center justify-center text-white font-black text-xl shadow-lg transform group-hover:scale-105 transition-transform">
                        {{ $initials }}
                    </div>
                    @if($conversation->status === 'open')
                        <div
                            class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full bg-wa-green border-4 border-white dark:border-slate-800 shadow-sm">
                        </div>
                    @endif
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-baseline mb-0.5">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate tracking-tight">
                            {{ $conversation->contact->name ?? $conversation->contact->phone_number }}
                        </h3>
                        <span class="text-[10px] uppercase font-bold text-slate-400">
                            {{ $conversation->last_message_at ? $conversation->last_message_at->format('H:i') : '' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate font-medium">
                        {{ $conversation->lastMessage ? Str::limit($conversation->lastMessage->content ?? '[Media]', 40) : 'Start chatting...' }}
                    </p>
                </div>

                <!-- Right Side Actions/Badges -->
                <div class="ml-2 flex flex-col items-end gap-2">
                    @if($conversation->assignee)
                        <img src="{{ $conversation->assignee->profile_photo_url }}"
                            class="h-5 w-5 rounded-full ring-2 ring-white dark:ring-slate-700 shadow-sm grayscale group-hover:grayscale-0 transition-all">
                    @endif

                    @if($conversation->status === 'closed')
                        <span
                            class="text-[9px] font-black uppercase text-slate-400 bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">Closed</span>
                    @endif
                </div>
            </div>
        @empty
            <div
                class="p-8 text-center bg-white/50 dark:bg-slate-800/50 rounded-2xl m-4 border border-dashed border-slate-300 dark:border-slate-700">
                <div class="text-slate-300 dark:text-slate-600 mb-3 flex justify-center">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-slate-500">No active chats</p>
                <p class="text-xs text-slate-400 mt-1">They'll appear here when you get a message.</p>
            </div>
        @endforelse
    </div>
</div>