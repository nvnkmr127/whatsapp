<div x-data="{ mobilePane: 'list' }"
    class="h-[calc(100vh-theme(spacing.32))] flex overflow-hidden bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800">
    <!-- Left Sidebar: Conversation List -->
    <div :class="{ 'hidden': mobilePane !== 'list', 'flex': mobilePane === 'list' }"
        class="w-full lg:w-80 xl:w-96 border-r border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 lg:flex flex-col">
        <livewire:chat.conversation-list wire:model="activeConversationId" />
    </div>

    <!-- Center: Message Window -->
    <div :class="{ 'hidden': mobilePane !== 'messages', 'flex': mobilePane === 'messages' }"
        class="flex-1 lg:flex flex-col bg-slate-100 dark:bg-slate-950 relative">
        @if($activeConversationId)
            <div class="lg:hidden absolute top-4 left-4 z-10">
                <button @click="mobilePane = 'list'"
                    class="p-2 bg-white dark:bg-slate-800 rounded-full shadow-md text-slate-600 dark:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>
            <livewire:chat.message-window :conversation-id="$activeConversationId" :key="'window-' . $activeConversationId" />
        @else
            <div
                class="flex-1 flex items-center justify-center flex-col text-slate-400 dark:text-slate-600 p-8 text-center">
                <div class="w-24 h-24 mb-6 rounded-full bg-slate-100 dark:bg-slate-900 flex items-center justify-center">
                    <svg class="w-12 h-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200 mb-2">Your Chat Room</h3>
                <p class="max-w-xs">Select a conversation from the left to start chatting with your customers.</p>
            </div>
        @endif
    </div>

    <!-- Right Sidebar: Contact Info -->
    @if($activeConversationId)
        <div
            class="hidden xl:flex w-80 border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex-col overflow-y-auto">
            <livewire:chat.contact-details :conversation-id="$activeConversationId" :key="'details-' . $activeConversationId" />
        </div>
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