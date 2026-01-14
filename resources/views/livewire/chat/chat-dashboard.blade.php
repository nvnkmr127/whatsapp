<div x-data="{ mobilePane: 'list', showDetails: true }" @toggle-details.window="showDetails = !showDetails"
    class="h-[calc(100vh-theme(spacing.32))] flex overflow-hidden bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 relative z-0">

    <!-- Left Sidebar: Active Channels -->
    <div :class="{ 'hidden': mobilePane !== 'list', 'flex': mobilePane === 'list' }"
        class="w-full lg:w-80 border-r border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 lg:flex flex-col z-10">
        <livewire:chat.conversation-list wire:model="activeConversationId" />
    </div>

    <!-- Center: Transmission Window -->
    <div :class="{ 'hidden': mobilePane !== 'messages', 'flex': mobilePane === 'messages' }"
        class="flex-1 lg:flex flex-col bg-white dark:bg-slate-950 relative z-0">
        @if($activeConversationId)
            <div class="lg:hidden absolute top-4 left-4 z-20">
                <button @click="mobilePane = 'list'"
                    class="p-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700 shadow-lg text-slate-500 hover:text-wa-teal transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>
            <livewire:chat.message-window :conversation-id="$activeConversationId" :key="'window-' . $activeConversationId" />
        @else
            <div
                class="flex-1 flex items-center justify-center flex-col text-slate-400 dark:text-slate-600 p-8 text-center bg-dots-pattern">
                <div
                    class="w-24 h-24 mb-6 rounded-[2rem] bg-slate-50 dark:bg-slate-900/50 flex items-center justify-center border border-slate-100 dark:border-slate-800">
                    <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-sm font-black text-slate-300 dark:text-slate-500 uppercase tracking-widest mb-2">Comms
                    Offline</h3>
                <p class="text-xs font-medium text-slate-400 dark:text-slate-600 max-w-xs">Select an active channel from the
                    list to establish a secure transmission link.</p>
            </div>
        @endif
    </div>

    <!-- Right Sidebar: Intelligence Profile -->
    @if($activeConversationId)
        <template x-if="showDetails">
            <div
                class="hidden xl:flex w-72 border-l border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex-col overflow-y-auto z-10 animate-in slide-in-from-right duration-300">
                <livewire:chat.contact-details :conversation-id="$activeConversationId" :key="'details-' . $activeConversationId" />
            </div>
        </template>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('conversationSelected', () => {
                if (window.innerWidth < 1024) {
                    Alpine.store('mobileChat', { pane: 'messages' }); // Wait, use x-data
                }
            });
        });
    </script>
</div>