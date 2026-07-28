<div x-data="{ 
    expanded: false,
    showPanel: $wire.entangle('isOpen'),
    unreadCount: $wire.entangle('unreadCount')
}" x-init="console.log('ChatBeacon initialized. unreadCount:', unreadCount)"
    class="fixed bottom-8 right-8 z-[100] flex flex-col items-end gap-4"
    @play-sound.window="document.getElementById('global-notification-sound').play().catch(e => console.log('Autoplay blocked'))">

    <audio id="global-notification-sound" src="/sounds/chat-beacon.mp3" preload="metadata"></audio>


    <!-- Floating Inbox Panel (Slide-In) -->
    <div x-show="showPanel" x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-10 opacity-0 scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-y-0 opacity-100 scale-100"
        x-transition:leave-end="translate-y-10 opacity-0 scale-95"
        class="bg-white dark:bg-slate-900 w-[400px] h-[600px] rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.2)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.4)] border border-slate-100 dark:border-slate-800 flex flex-col overflow-hidden mb-4"
        x-cloak>

        <!-- Panel Header -->
        <div
            class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 backdrop-blur-md">
            <div>
                <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Quick <span
                        class="text-wa-teal">Inbox</span></h2>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $unreadCount }} Unread
                    Signals</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('chat') }}"
                    class="p-2 hover:bg-wa-teal/10 rounded-xl transition-colors text-slate-400 hover:text-wa-teal"
                    title="Full Inbox">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
                <button @click="showPanel = false"
                    class="p-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl transition-colors text-slate-400 hover:text-rose-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Panel Content -->
        <div class="flex-1 overflow-hidden flex flex-col">
            @if($activeConversationId)
                <div class="flex-1 overflow-hidden relative">
                    <button @click="$set('activeConversationId', null)"
                        class="absolute top-4 left-4 z-10 p-2 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 text-slate-500 hover:text-wa-teal transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <livewire:chat.message-window :conversationId="$activeConversationId" :key="'beacon-chat-' . $activeConversationId" />
                </div>
            @else
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <livewire:chat.conversation-list wire:model="activeConversationId" />
                </div>
            @endif
        </div>
    </div>

    <!-- Floating Beacon -->
    <div class="relative group" @mouseenter="expanded = true" @mouseleave="expanded = false">

        <!-- Preview Tooltip -->
        <div x-show="expanded && !showPanel && unreadCount > 0" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
            class="absolute right-[calc(100%+16px)] top-1/2 -translate-y-1/2 bg-white dark:bg-slate-900 px-4 py-3 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 w-48 pointer-events-none"
            x-cloak>
            <p class="text-[8px] font-black text-wa-teal uppercase tracking-widest mb-1">Latest Signal</p>
            <p class="text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate">
                {{ $latestMessage ? $latestMessage->content : 'No signals' }}
            </p>
        </div>

        <!-- Main Button -->
        <button @click="showPanel = !showPanel"
            class="relative h-16 w-16 bg-white dark:bg-slate-900 rounded-[1.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 group-hover:shadow-wa-teal/20"
            :class="showPanel ? 'bg-wa-teal border-wa-teal text-white' : 'text-wa-teal'">

            <!-- WhatsApp Icon -->
            <svg class="w-8 h-8 transition-transform duration-500"
                :class="showPanel ? 'rotate-[360deg] scale-75' : 'group-hover:rotate-12'" fill="currentColor"
                viewBox="0 0 24 24">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.94 3.659 1.437 5.634 1.437h.005c6.551 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>

            <!-- Unread Count Badge -->
            <div x-show="unreadCount > 0"
                class="absolute -top-2 -right-2 h-7 min-w-[28px] px-2 flex items-center justify-center rounded-full bg-wa-teal text-white text-xs font-black border-4 border-white dark:border-slate-950 shadow-xl animate-bounce"
                x-cloak>
                <span x-text="unreadCount"></span>
            </div>

            <!-- Pulse Effect -->
            <div x-show="unreadCount > 0 && !showPanel"
                class="absolute inset-0 rounded-[1.5rem] bg-wa-teal/10 animate-ping -z-10"></div>
        </button>
    </div>
</div>