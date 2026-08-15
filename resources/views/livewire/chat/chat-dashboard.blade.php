<div x-data="{ mobilePane: '{{ $activeConversationId ? "messages" : "list" }}', showDetails: true }" @toggle-details.window="showDetails = !showDetails"
    @toggle-mobile-pane.window="mobilePane = $event.detail"
    class="h-[calc(100dvh-theme(spacing.32))] flex overflow-hidden bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 relative z-0">

    <!-- Left Sidebar: Active Channels -->
    <div :class="{ 'hidden': mobilePane !== 'list', 'flex': mobilePane === 'list' }"
        class="w-full lg:w-80 border-r border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 lg:flex flex-col z-10 shrink-0">
        <livewire:chat.conversation-list wire:model="activeConversationId" />
    </div>

    <!-- Center: Transmission Window -->
    <div :class="{ 'hidden': mobilePane !== 'messages', 'flex': mobilePane === 'messages' }"
        class="flex-1 min-w-0 lg:flex flex-col bg-white dark:bg-slate-950 relative z-0">
        @if($activeConversationId)
            <!-- Instant Loading Placeholder -->
            <div wire:loading wire:target="activeConversationId"
                class="absolute inset-0 flex items-center justify-center bg-white dark:bg-slate-950 z-10">
                <div class="text-center">
                    <div class="w-16 h-16 mb-4 rounded-2xl bg-wa-teal/10 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-wa-teal animate-pulse" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Loading conversation...</p>
                </div>
            </div>

            {{-- Not lazy: it renders in the same roundtrip that selects the conversation,
                 and the first page of messages is already embedded in that render. --}}
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
        <div x-show="showDetails" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-full"
            class="hidden xl:flex w-72 border-l border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex-col overflow-y-auto z-10">
            {{-- Lazy on purpose: secondary info, and it's hidden below xl anyway.
                 Keeps the sidebar's queries off the chat-open critical path. --}}
            <livewire:chat.contact-details :conversation-id="$activeConversationId" :key="'details-' . $activeConversationId"
                lazy />
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            // On mobile, show the message pane whenever a conversation is selected
            // (row click, deep link, or programmatic). The dashboard's Alpine root
            // listens for toggle-mobile-pane and flips mobilePane.
            @this.on('conversationSelected', () => {
                if (window.innerWidth < 1024) {
                    window.dispatchEvent(new CustomEvent('toggle-mobile-pane', { detail: 'messages' }));
                }
            });
        });
    </script>
</div>