<div class="flex flex-col flex-1 min-h-0 h-full relative bg-dots-pattern" x-data="{ 
        init() {
            $store.chat.setMyUser({{ auth()->id() }});
            $store.chat.init($wire, {{ $conversationId ?? 0 }}, {{ auth()->user()->currentTeam->id ?? 0 }});
        }
    }"
    @set-message-body.window="window.dispatchEvent(new CustomEvent('update-message-body', { detail: $event.detail }))">

    <!-- Connection Status Banner -->
    <div x-show="$store.chat.connectionState !== 'connected'" x-cloak x-transition
        class="w-full z-40 bg-rose-500/10 dark:bg-rose-900/20 py-2 border-b border-rose-200 dark:border-rose-900/50 flex items-center justify-center">
        <div class="text-rose-600 dark:text-rose-400 text-xs font-bold inline-flex items-center gap-2">
            <template x-if="$store.chat.connectionState === 'connecting'">
                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </template>
            <template x-if="$store.chat.connectionState === 'offline'">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>
            <span
                x-text="$store.chat.connectionState === 'connecting' ? 'Reconnecting to chat...' : 'You are currently offline'"></span>
        </div>
    </div>

    <style>
        @keyframes waveform {

            0%,
            100% {
                transform: scaleY(1);
            }

            50% {
                transform: scaleY(2.5);
            }
        }

        .animate-waveform {
            animation: waveform 0.8s ease-in-out infinite;
        }

        .message-appear {
            animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <!-- Header -->
    <div
        class="px-6 py-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-100 dark:border-slate-800 flex justify-between items-center z-10 sticky top-0 shadow-sm">
        @if($conversation)
            <div class="flex items-center">
                <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $conversation->contact->name ?? 'Unknown' }}"
                    alt="{{ $conversation->contact->name ?? 'Unknown' }}"
                    class="h-10 w-10 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shadow-lg shadow-wa-teal/20 mr-4">
                <div>
                    <h2
                        class="font-black text-slate-900 dark:text-white tracking-tight leading-none uppercase text-sm mb-0.5 whitespace-nowrap">
                        {{ $conversation->contact->name ?? $conversation->contact->phone_number }}
                    </h2>
                    <div class="text-[10px] font-bold text-slate-500 flex items-center gap-2 uppercase tracking-wide">
                        <span class="text-wa-teal">{{ $conversation->contact->phone_number }}</span>

                        <span x-show="$store.chat.isTyping" x-transition
                            class="text-wa-teal animate-pulse font-black flex items-center gap-1">
                            <span x-text="$store.chat.typingUser"></span> IS TYPING...
                        </span>

                        <span x-show="$store.chat.isCustomerTyping" x-transition
                            class="text-emerald-500 animate-bounce font-black flex items-center gap-1">
                            CUSTOMER IS TYPING...
                        </span>

                        <template x-if="$store.chat.activeUsers.length > 1">
                            <div
                                class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full border border-slate-200 dark:border-slate-700">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-wa-teal opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-wa-teal"></span>
                                </span>
                                <span x-text="$store.chat.activeUsers.length + ' AGENTS ONLINE'"></span>
                            </div>
                        </template>

                        @if($conversation->last_message_at)
                            <span class="text-slate-300 dark:text-slate-700"
                                x-show="!$store.chat.isTyping && !$store.chat.isCustomerTyping">|</span>
                            <span class="{{ $conversation->last_message_at->diffInHours() > 24 ? 'text-rose-500' : '' }}"
                                x-show="!$store.chat.isTyping && !$store.chat.isCustomerTyping">
                                {{ $conversation->last_message_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>

                    <!-- Active Tags -->
                    @php $activeTags = $conversation->metadata['tags'] ?? []; @endphp
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($activeTags as $tagId)
                            @php $category = collect($availableCategories)->firstWhere('id', $tagId); @endphp
                            @if($category)
                                <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider shadow-sm"
                                    style="background-color: {{ $category->color }}20; color: {{ $category->color }}; border: 1px solid {{ $category->color }}30">
                                    {{ $category->name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($conversation)
            <!-- Actions -->
            <div class="flex items-center gap-4">
                <!-- Agent Presence Stacks -->
                <div class="hidden lg:flex -space-x-2 overflow-hidden mr-2">
                    @foreach($activeAgents as $agent)
                        @if($agent['id'] != Auth::id())
                            <div class="inline-block h-8 w-8 rounded-full ring-4 ring-white dark:ring-slate-900 bg-slate-100 dark:bg-slate-800 border-2 border-wa-teal/20"
                                title="{{ $agent['name'] }} viewing">
                                <img class="h-full w-full rounded-full"
                                    src="https://api.dicebear.com/9.x/micah/svg?seed={{ $agent['name'] }}"
                                    alt="{{ $agent['name'] }}">
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Tagging Action -->
                <div x-data="{ showTags: false }" class="relative">
                    <button @click="showTags = !showTags"
                        class="p-2.5 bg-slate-50 dark:bg-slate-800 hover:bg-wa-teal/10 rounded-xl transition-all text-slate-500 hover:text-wa-teal border border-slate-100 dark:border-slate-700"
                        title="Label Conversation">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </button>
                    <div x-show="showTags" x-cloak @click.away="showTags = false"
                        class="absolute top-12 right-0 bg-white dark:bg-slate-900 shadow-2xl border border-slate-100 dark:border-slate-800 rounded-2xl p-4 z-50 w-56 animate-in fade-in zoom-in duration-200">
                        <p
                            class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 dark:border-slate-800 pb-2">
                            Label Categories</p>
                        <div class="space-y-1">
                            @foreach($availableCategories as $category)
                                <button wire:click="toggleCategory({{ $category->id }})"
                                    class="w-full flex items-center justify-between p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors group">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full" style="background-color: {{ $category->color }}">
                                        </div>
                                        <span
                                            class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $category->name }}</span>
                                    </div>
                                    @if(isset($activeTags) && in_array($category->id, $activeTags))
                                        <svg class="w-4 h-4 text-wa-teal" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                            @if($availableCategories->isEmpty())
                                <p class="text-[10px] text-slate-400 italic p-2 line-height-relaxed">No categories defined.
                                    Configure them in Settings Hub.</p>
                            @endif
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <a href="{{ route('settings.categories') }}" target="_blank"
                                class="flex items-center gap-2 px-2 py-1.5 text-[10px] font-black text-wa-teal uppercase tracking-widest hover:bg-wa-teal/5 rounded-lg transition-all">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Manage Labels
                            </a>
                        </div>
                    </div>
                </div>
                <div x-data="{ showTransferModal: false }" class="relative">
                    <button @click="showTransferModal = !showTransferModal"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-wa-teal"
                        title="Transfer Conversation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </button>
                    <!-- Transfer Modal -->
                    <div x-show="showTransferModal" x-cloak
                        class="absolute top-12 right-0 bg-white dark:bg-slate-900 shadow-2xl border border-slate-100 dark:border-slate-800 rounded-2xl p-4 z-50 w-64 animate-in fade-in zoom-in duration-200"
                        @click.away="showTransferModal = false">
                        <p
                            class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 dark:border-slate-800 pb-2">
                            Assign to Agent</p>
                        <div class="space-y-1 max-h-48 overflow-y-auto custom-scrollbar">
                            @foreach($this->agents as $agent)
                                <button wire:click="transferConversation({{ $agent->id }})" @click="showTransferModal = false"
                                    class="w-full flex items-center gap-3 p-2 hover:bg-wa-teal/5 rounded-xl transition-colors text-left group">
                                    <div
                                        class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-500 group-hover:bg-wa-teal/10 group-hover:text-wa-teal">
                                        {{ substr($agent->name, 0, 1) }}
                                    </div>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $agent->name }}</span>
                                </button>
                            @endforeach
                            @if($this->agents->isEmpty())
                                <p class="text-[10px] text-slate-400 italic p-2">No other agents online.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <livewire:chat.whatsapp-call-button :contact="$conversation->contact" :key="'call-' . $conversation->id" />

                <div class="flex items-center gap-2" x-data="{ showCloseModal: false }">
                    <button @click="$dispatch('toggle-details')"
                        class="hidden xl:flex p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-wa-teal"
                        title="Toggle Contact Info">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>

                    <button wire:click="toggleBot"
                        class="p-2 rounded-xl transition-all {{ ($conversation->contact->is_bot_paused ?? false) ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-500' }} hover:scale-105"
                        title="{{ ($conversation->contact->is_bot_paused ?? false) ? 'Resume Bot' : 'Pause Bot' }}">
                        @if($conversation->contact->is_bot_paused ?? false)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </button>

                    @if($conversation->status !== 'closed')
                        <button @click="showCloseModal = !showCloseModal"
                            class="p-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl transition-colors text-slate-400 hover:text-rose-500"
                            title="Close Conversation">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif

                    <!-- Modal -->
                    <div x-show="showCloseModal" x-cloak
                        class="absolute top-16 right-6 bg-white dark:bg-slate-900 shadow-2xl border border-slate-100 dark:border-slate-800 rounded-2xl p-4 z-50 w-64 animate-in fade-in zoom-in duration-200"
                        @click.away="showCloseModal = false">
                        <p
                            class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 dark:border-slate-800 pb-2">
                            Close Conversation</p>
                        <div class="grid grid-cols-1 gap-2">
                            <button wire:click="closeConversation('resolved')" @click="showCloseModal = false"
                                class="flex items-center px-4 py-3 text-[10px] font-black uppercase tracking-wider hover:bg-emerald-50 dark:hover:bg-emerald-900/10 rounded-xl text-emerald-600 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span> Resolved
                            </button>
                            <button wire:click="closeConversation('spam')" @click="showCloseModal = false"
                                class="flex items-center px-4 py-3 text-[10px] font-black uppercase tracking-wider hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl text-rose-600 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></span> Spam
                            </button>
                            <button wire:click="closeConversation('timeout')" @click="showCloseModal = false"
                                class="flex items-center px-4 py-3 text-[10px] font-black uppercase tracking-wider hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-slate-500 transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-2"></span> No Response
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50 dark:bg-slate-950 relative" id="messages-container" x-data="{
            itemHeight: 80, // Average height estimate
            buffer: 5,
            viewportHeight: 0,
            scrollTop: 0,
             init() {
                // Drag and Drop Listeners
                window.addEventListener('dragover', (e) => { e.preventDefault(); $store.chat.isDragging = true; });
                window.addEventListener('dragleave', (e) => { e.preventDefault(); if (e.relatedTarget === null) $store.chat.isDragging = false; });
                window.addEventListener('drop', (e) => {
                    e.preventDefault();
                    $store.chat.isDragging = false;
                    if (e.dataTransfer.files.length > 0) {
                        @this.upload('newAttachment', e.dataTransfer.files[0]);
                    }
                });
                
                this.viewportHeight = this.$el.clientHeight;
                
                // Debug logs
                console.log('MessageWindow: Init', { 
                    convId: {{ $conversationId ?? 0 }}, 
                    viewport: this.viewportHeight 
                });

                // Initialize Scroll
                this.$watch('$store.chat.messages', (val, old) => {
                   console.log('MessageWindow: Messages Updated', { count: val.length, old: old.length });
                   if (old.length === 0 && val.length > 0) {
                       this.$nextTick(() => this.scrollToBottom());
                   }
                });
                
                // Event Listeners
                window.addEventListener('chat-scroll-bottom', () => this.scrollToBottom());
                window.addEventListener('chat-initial-loaded', () => {
                    console.log('MessageWindow: Initial Load Complete', $store.chat.messages);
                    this.scrollToBottom();
                });
            },
            scrollToBottom() {
                this.$el.scrollTop = this.$el.scrollHeight;
            },
            handleScroll(e) {
                this.scrollTop = e.target.scrollTop;
                // Load More Trigger
                if (this.scrollTop < 100 && $store.chat.messages.length > 0) {
                    const oldHeight = this.$el.scrollHeight;
                    const oldTop = this.$el.scrollTop;
                    $store.chat.loadMessages().then(() => {
                        this.$nextTick(() => {
                            const newHeight = this.$el.scrollHeight;
                            if (newHeight > oldHeight) {
                                this.$el.scrollTop = newHeight - oldHeight + oldTop;
                            }
                        });
                    });
                }
            },
            get startIndex() {
                return Math.floor(this.scrollTop / this.itemHeight);
            },
            get renderConfig() {
                // Return start index and end index
                // Note: Simple virtualization. For complex bubbles, use a library or just raw render if < 200 items.
                const count = $store.chat.messages.length;
                console.log('MessageWindow: RenderConfig Calc', { count, scrollTop: this.scrollTop });
                
                if (count < 100) return { start: 0, end: count, top: 0, bottom: 0 };
                
                let start = Math.max(0, this.startIndex - this.buffer);
                let visibleCount = Math.ceil(this.viewportHeight / this.itemHeight) + (2 * this.buffer);
                let end = Math.min(count, start + visibleCount);
                
                let topH = start * this.itemHeight;
                let bottomH = (count - end) * this.itemHeight;
                
                return { start, end, top: topH, bottom: bottomH };
            },
            get visibleMessages() {
                const conf = this.renderConfig;
                return $store.chat.messages.slice(conf.start, conf.end);
            }
         }" @scroll.passive="handleScroll" x-init="init()">

        <div class="flex justify-center mb-8" :style="{ marginTop: renderConfig.top + 'px' }">
            <span
                class="px-4 py-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-[9px] font-bold text-amber-700 dark:text-amber-400 tracking-wide border border-amber-200 dark:border-amber-800 flex items-center gap-2 shadow-sm">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                </svg>
                Messages are end-to-end encrypted
            </span>
        </div>

        <!-- Drag and Drop Overlay -->
        <div x-show="$store.chat.isDragging" x-cloak x-transition
            class="absolute inset-0 z-50 bg-wa-teal/10 backdrop-blur-[2px] border-4 border-dashed border-wa-teal m-4 rounded-[2rem] flex flex-col items-center justify-center pointer-events-none">
            <div class="p-6 bg-white dark:bg-slate-900 rounded-3xl shadow-2xl flex flex-col items-center gap-4">
                <div
                    class="w-16 h-16 bg-wa-teal/10 text-wa-teal rounded-full flex items-center justify-center animate-bounce">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <p class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white">Drop to upload
                    media</p>
            </div>
        </div>

        <template x-for="(message, index) in visibleMessages" :key="message.id">
            <div class="w-full">
                <!-- Date Divider -->
                <template x-if="$store.chat.shouldShowDateDivider(index)">
                    <div class="flex justify-center my-8 sticky top-4 z-20">
                        <span
                            class="px-6 py-2 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md rounded-full text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] shadow-xl shadow-slate-200/20 dark:shadow-black/20 border border-white/20 dark:border-slate-800/50 transition-all hover:scale-105"
                            x-text="$store.chat.getDateLabel(message.created_at)"></span>
                    </div>
                </template>

                <!-- Call Log Entry -->
                <template x-if="message.type === 'call_log'">
                    <div class="flex justify-center mb-8 px-4">
                        <div
                            class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/20 overflow-hidden group/call">
                            <!-- Card Header -->
                            <div
                                class="p-5 flex items-center justify-between border-b border-slate-50 dark:border-slate-800/50">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 rounded-2xl"
                                        :class="message.metadata?.status === 'completed' ? 'bg-wa-teal/10 text-wa-teal' : 'bg-rose-500/10 text-rose-500'">
                                        <template x-if="message.metadata?.status === 'completed'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 8l2-2m0 0l2 2m-2-2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h2M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-.311-.104a4.501 4.501 0 00-1.456-.272H10.16c-.445 0-.882.044-1.308.13l-1.488.3a4.502 4.502 0 01-1.456.272H5.16c-.618 0-1.217-.247-1.605-.729a11.95 11.95 0 01-2.649-7.521c0-.435.023-.863.068-1.285.109-1.021 1.028-1.715 2.054-1.715h3.605z" />
                                            </svg>
                                        </template>
                                        <template x-if="message.metadata?.status !== 'completed'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 8l2-2m0 0l2 2m-2-2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h2M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-.311-.104a4.501 4.501 0 00-1.456-.272H10.16c-.445 0-.882.044-1.308.13l-1.488.3a4.502 4.502 0 01-1.456.272H5.16c-.618 0-1.217-.247-1.605-.729a11.95 11.95 0 01-2.649-7.521c0-.435.023-.863.068-1.285.109-1.021 1.028-1.715 2.054-1.715h3.605z" />
                                            </svg>
                                        </template>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400"
                                            x-text="message.metadata?.status === 'completed' ? 'VoIP Session' : 'Missed Interaction'"></span>
                                        <span class="text-sm font-black text-slate-900 dark:text-white"
                                            x-text="message.content"></span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400" x-text="message.pretty_time"></span>
                            </div>

                            <!-- Card Body (Post-Call Actions & Notes) -->
                            <div class="p-5 space-y-4"
                                x-data="{ editingNote: false, noteValue: message.metadata?.agent_note || '' }">
                                <!-- Notes Display/Input -->
                                <div class="relative">
                                    <template x-if="!editingNote && !noteValue">
                                        <button @click="editingNote = true"
                                            class="w-full py-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 text-[10px] font-black text-slate-400 uppercase tracking-[0.1em] hover:bg-slate-100 transition-all flex items-center justify-center gap-2">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Add Summary Note
                                        </button>
                                    </template>
                                    <template x-if="!editingNote && noteValue">
                                        <div class="p-4 bg-amber-50/50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/20 relative group/note cursor-pointer"
                                            @click="editingNote = true">
                                            <span
                                                class="text-xs font-medium text-amber-900 dark:text-amber-300 leading-relaxed"
                                                x-text="noteValue"></span>
                                            <div
                                                class="absolute inset-0 bg-white/40 dark:bg-black/40 backdrop-blur-[1px] opacity-0 group-hover/note:opacity-100 transition-opacity flex items-center justify-center rounded-2xl">
                                                <span
                                                    class="text-[9px] font-black text-slate-900 dark:text-white uppercase">Click
                                                    to edit</span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="editingNote">
                                        <div class="space-y-2">
                                            <textarea x-model="noteValue"
                                                class="w-full p-4 bg-white dark:bg-slate-800 border-2 border-wa-teal/20 rounded-2xl text-xs font-medium focus:ring-0 focus:border-wa-teal transition-all"
                                                rows="3" placeholder="Summarize the outcome..."></textarea>
                                            <div class="flex justify-end gap-2">
                                                <button @click="editingNote = false"
                                                    class="px-4 py-2 text-[10px] font-black text-slate-400 uppercase">Cancel</button>
                                                <button
                                                    @click="$wire.saveCallNote(message.id, noteValue); editingNote = false; message.metadata.agent_note = noteValue"
                                                    class="px-4 py-2 bg-wa-teal text-white rounded-xl text-[10px] font-black uppercase">Save
                                                    Note</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Suggested Actions -->
                                <div class="pt-2 flex flex-wrap gap-2">
                                    <template x-if="message.metadata?.status !== 'completed'">
                                        <button @click="$wire.openTemplateList()"
                                            class="px-3 py-2 bg-wa-teal/10 text-wa-teal hover:bg-wa-teal text-[9px] font-black uppercase tracking-widest rounded-xl hover:text-white transition-all border border-wa-teal/20">
                                            🚀 Send Follow-up
                                        </button>
                                    </template>
                                    <button
                                        class="px-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all border border-slate-200 dark:border-slate-700">
                                        🗓️ Schedule Task
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Internal Note -->
                <template x-if="message.type === 'internal_note'">
                    <div class="flex justify-center mb-6 px-10">
                        <div
                            class="px-6 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 rounded-2xl shadow-sm text-center relative max-w-md group">
                            <div
                                class="absolute -top-2 left-4 px-2 bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400 text-[8px] font-black uppercase tracking-widest rounded-full">
                                Internal Note
                            </div>
                            <p class="text-[11px] font-bold text-amber-900 dark:text-amber-200 leading-relaxed italic"
                                x-text="message.content"></p>
                            <div
                                class="mt-1 text-[8px] font-black text-amber-600/50 uppercase tracking-widest flex items-center justify-center gap-2">
                                <span x-text="message.metadata?.agent_name || 'System'"></span>
                                <span>•</span>
                                <span x-text="message.pretty_time"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Standard Message Bubble -->
                <template x-if="message.type !== 'call_log' && message.type !== 'internal_note'">
                    <div
                        :class="['flex', message.is_outbound ? 'justify-end' : 'justify-start', 'mb-6 message-appear relative group/msg']">
                        <!-- Reaction Picker (Hover) -->
                        <div class="absolute -top-8 z-30 opacity-0 group-hover/msg:opacity-100 transition-opacity flex items-center gap-1 bg-white dark:bg-slate-800 shadow-2xl border border-slate-100 dark:border-slate-700 rounded-full px-2 py-1"
                            :class="message.is_outbound ? 'right-0' : 'left-0'">
                            @foreach(['👍', '❤️', '😂', '😮', '😢', '🙏'] as $emoji)
                                <button @click="$wire.addReaction(message.id, '{{ $emoji }}')"
                                    class="hover:scale-125 transition-transform p-1 text-sm">{{ $emoji }}</button>
                            @endforeach
                        </div>

                        <div class="max-w-[85%] sm:max-w-[70%] group relative">
                            <div :class="[
                            'relative p-3 px-4 transition-all hover:scale-[1.01] shadow-sm',
                            message.is_outbound 
                                ? 'bg-wa-teal text-white rounded-2xl rounded-tr-sm shadow-xl shadow-wa-teal/10' 
                                : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-sm border border-slate-100 dark:border-slate-700'
                        ]">
                                <!-- Attribution Badge -->
                                <template x-if="message.attributed_campaign_name">
                                    <div
                                        class="mb-2 flex items-center gap-1.5 px-2 py-1 rounded-lg bg-wa-teal/10 dark:bg-wa-teal/20 border border-wa-teal/20">
                                        <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167a2.405 2.405 0 011.002-2.736l3.144-1.921A1.76 1.76 0 0111 5.882zM15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="text-[9px] font-black text-wa-teal uppercase tracking-widest">
                                            Reply to: <span x-text="message.attributed_campaign_name"></span>
                                        </span>
                                    </div>
                                </template>

                                <!-- Media -->
                                <template x-if="message.media_url">
                                    <div class="mb-3 rounded-lg overflow-hidden border border-white/10">
                                        <template x-if="message.media_type && message.media_type.startsWith('image')">
                                            <img :src="message.media_url"
                                                class="w-full max-h-80 object-cover cursor-pointer hover:opacity-90 rounded-lg shadow-sm"
                                                @click="$store.chat.lightboxImage = message.media_url; $store.chat.lightboxOpen = true">
                                        </template>
                                        <template x-if="message.media_type && message.media_type.startsWith('video')">
                                            <video :src="message.media_url" controls class="w-full max-h-80"></video>
                                        </template>
                                        <template x-if="message.media_type && message.media_type.startsWith('audio')">
                                            <div class="bg-black/5 dark:bg-black/20 rounded-xl p-1">
                                                <audio :src="message.media_url" controls
                                                    class="w-full h-8 flex"></audio>
                                            </div>
                                        </template>
                                        <template
                                            x-if="message.media_type && !['image','video','audio'].some(t => message.media_type.startsWith(t))">
                                            <a :href="message.media_url" target="_blank"
                                                class="flex items-center gap-3 p-3 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="font-bold text-xs truncate">Document</span>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                <!-- Text -->
                                <template x-if="message.content && message.content !== '[Image]'">
                                    <p class="text-xs sm:text-sm font-medium whitespace-pre-wrap leading-relaxed"
                                        x-text="message.content"></p>
                                </template>

                                <!-- Caption -->
                                <template x-if="message.caption && !message.content">
                                    <p class="text-xs font-bold italic opacity-80 mt-1" x-text="message.caption"></p>
                                </template>

                                <!-- Metadata -->
                                <div
                                    class="text-[9px] font-black uppercase tracking-widest mt-2 flex items-center justify-end gap-1.5 opacity-60">
                                    <span x-text="message.pretty_time"></span>

                                    <template x-if="message.is_outbound">
                                        <span>
                                            <template x-if="message.status === 'read'">
                                                <svg class="w-3 h-3 text-sky-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7M5 7l4 4 10-10" />
                                                </svg>
                                            </template>
                                            <template x-if="message.status === 'delivered'">
                                                <svg class="w-3 h-3 text-white/70" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7M5 7l4 4 10-10" />
                                                </svg>
                                            </template>
                                            <template x-if="message.status === 'sent'">
                                                <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </template>
                                            <template x-if="message.status === 'failed'">
                                                <div class="group/error relative flex items-center gap-1">
                                                    <span
                                                        class="text-[8px] font-black text-rose-300 uppercase cursor-pointer hover:underline"
                                                        @click="$store.chat.retryMessage(message.id)">Retry</span>
                                                    <svg class="w-3 h-3 text-rose-300 cursor-help" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            </template>
                                            <template x-if="['queued', 'sending'].includes(message.status)">
                                                <svg class="w-3 h-3 text-white/40 animate-pulse" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </template>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Active Reactions -->
                            <template
                                x-if="message.metadata?.reactions && Object.keys(message.metadata.reactions).length > 0">
                                <div class="absolute -bottom-3 flex flex-wrap gap-0.5"
                                    :class="message.is_outbound ? 'right-2' : 'left-2'">
                                    <template x-for="(emoji, agentId) in message.metadata.reactions">
                                        <div class="bg-white dark:bg-slate-700 shadow-md border border-slate-100 dark:border-slate-600 rounded-full px-1.5 py-0.5 text-[10px] transform hover:scale-110 transition-transform cursor-pointer"
                                            :title="'Agent ' + agentId">
                                            <span x-text="emoji"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <div :style="{ height: renderConfig.bottom + 'px' }"></div>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-t border-slate-100 dark:border-slate-800 z-10 shrink-0"
        x-data="{
            msgBody: '',
            showAttach: false,
            showEmoji: false,
            showQR: false,
            qrFilter: '',
            quickReplies: {{ \Illuminate\Support\Js::from($quickReplies) }},
            checkQR() {
                const val = this.msgBody || '';
                const match = val.match(/\/(.*)$/);
                if (match) {
                    this.showQR = true;
                    this.qrFilter = match[1].toLowerCase();
                } else {
                    this.showQR = false;
                }
            },
            selectQR(text) {
                const val = this.msgBody || '';
                this.msgBody = val.replace(/\/(.*)$/, text);
                this.showQR = false;
                $refs.messageInput.focus();
            },
            insertEmoji(emoji) {
                this.msgBody = (this.msgBody || '') + emoji;
                this.showEmoji = false;
            },
             async handleSubmit() {
                if (this.msgBody.trim() === '' && !$wire.newAttachment) return;

                // Handle Internal Note
                if (this.isNoteMode) {
                    $wire.set('messageBody', this.msgBody);
                    await $wire.saveInternalNote();
                    this.msgBody = '';
                    this.isNoteMode = false;
                    return;
                }

                // Check for attachment (Legacy Path)
                if ($wire.newAttachment) {
                    $wire.set('messageBody', this.msgBody);
                    await $wire.sendMessage(); // Legacy
                    this.msgBody = '';
                    return;
                }

                // Text Only (Optimistic Path)
                const body = this.msgBody;
                this.msgBody = ''; // Clear immediately
                $store.chat.sendMessage(body);
            },
            isNoteMode: false,
            isRecording: false,
            recordingTime: '0:00',
            mediaRecorder: null,
            audioChunks: [],
            init() {
                window.addEventListener('update-message-body', (e) => {
                    this.msgBody = e.detail.body;
                    $refs.messageInput.focus();
                });
            },
            async startRecording() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.mediaRecorder = new MediaRecorder(stream);
                    this.audioChunks = [];
                    
                    this.mediaRecorder.ondataavailable = (e) => {
                        this.audioChunks.push(e.data);
                    };

                    this.mediaRecorder.onstop = async () => {
                        const audioBlob = new Blob(this.audioChunks, { type: 'audio/ogg; codecs=opus' });
                        if (this.shouldSendRecording) {
                           // Upload to Livewire
                           $wire.upload('newAttachment', audioBlob, (uploadedFilename) => {
                               $wire.sendVoiceNote(uploadedFilename);
                           });
                        }
                        stream.getTracks().forEach(track => track.stop());
                    };

                    this.mediaRecorder.start();
                    this.isRecording = true;
                    this.shouldSendRecording = false;

                    let sec = 0;
                    this.recInterval = setInterval(() => {
                        sec++;
                        let m = Math.floor(sec / 60);
                        let s = sec % 60;
                        this.recordingTime = `${m}:${s < 10 ? '0' : ''}${s}`;
                    }, 1000);
                } catch (err) {
                    alert('Could not access microphone: ' + err.message);
                }
            },
            stopRecording(send = true) {
                this.isRecording = false;
                this.shouldSendRecording = send;
                if (this.mediaRecorder) {
                    this.mediaRecorder.stop();
                }
                clearInterval(this.recInterval);
            }
         }" x-init="init()">

        @if($isSessionOpen)

            <!-- File Preview -->
            @if($newAttachment)
                <div
                    class="mb-4 p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-between animate-in slide-in-from-bottom-2">
                    <div class="flex items-center gap-3">
                        @if(Str::startsWith($newAttachment->getMimeType(), 'image'))
                            <img src="{{ $newAttachment->temporaryUrl() }}" class="h-12 w-12 rounded-lg object-cover">
                        @else
                            <div class="h-12 w-12 bg-white dark:bg-slate-700 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                {{ $newAttachment->getClientOriginalName() }}
                            </p>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 h-1 rounded-full mt-1 overflow-hidden"
                                wire:loading.class="animate-pulse">
                                <div class="bg-wa-teal h-full transition-all duration-300" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    <button wire:click="deleteAttachment"
                        class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-full text-slate-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            <!-- Voice Recording Overlay -->
            <div x-show="isRecording" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                class="mb-4 p-5 bg-gradient-to-r from-rose-600 to-rose-500 text-white rounded-[2.5rem] flex items-center justify-between shadow-2xl shadow-rose-500/20 relative overflow-hidden">
                <!-- Waveform Decoration -->
                <div class="absolute inset-0 opacity-10 flex items-center justify-center gap-1 pointer-events-none">
                    @foreach(range(1, 20) as $i)
                        <div class="w-1 bg-white rounded-full animate-waveform"
                            style="height: {{ rand(20, 80) }}%; animation-delay: {{ $i * 0.1 }}s"></div>
                    @endforeach
                </div>

                <div class="flex items-center gap-5 z-10">
                    <div class="relative">
                        <div class="w-3 h-3 rounded-full bg-white animate-ping"></div>
                        <div class="absolute inset-0 w-3 h-3 rounded-full bg-white"></div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-100">Live Recording</span>
                        <span class="text-lg font-mono font-black tabular-nums" x-text="recordingTime">0:00</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 z-10">
                    <button @click="stopRecording(false)"
                        class="p-3 bg-white/10 hover:bg-white/20 rounded-full transition-all hover:rotate-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button @click="stopRecording(true)"
                        class="px-8 py-3 bg-white text-rose-600 rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-lg hover:scale-105 active:scale-95 transition-all">
                        Send Note
                    </button>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="flex items-center gap-2 relative">

                <!-- Lock Banner -->
                <div x-show="$store.chat.isLockedForMe()" x-transition x-cloak
                    class="absolute bottom-full left-0 w-full mb-4 p-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs z-20">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="font-bold text-slate-500">
                            Reply Locked: <span class="text-slate-800 dark:text-slate-200"
                                x-text="$store.chat.lockedBy ? $store.chat.lockedBy.name : 'Another Agent'"></span>
                            is writing...
                        </span>
                    </div>
                    <button type="button" @click="$store.chat.takeOver()" class="text-wa-teal font-bold hover:underline">
                        Take Over
                    </button>
                </div>

                <!-- Hidden File Input -->
                <input type="file" wire:model="newAttachment" class="hidden" x-ref="fileInput"
                    x-on:livewire-upload-error="uploadError = 'File upload failed. The file may be too large (Server Limit) or the format is invalid.'; showUploadErrorModal = true;"
                    accept="image/*,video/*,audio/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">

                <!-- Attach Button (Popover) -->
                <div class="relative">
                    <button type="button" @click="if(!$store.chat.isLockedForMe()) showAttach = !showAttach"
                        :disabled="$store.chat.isLockedForMe()"
                        :class="$store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed' : 'hover:text-wa-teal hover:bg-wa-teal/5'"
                        class="p-3 text-slate-400 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>
                    <!-- ... attach menu ... -->
                    <div x-show="showAttach" @click.away="showAttach = false" x-cloak
                        class="absolute bottom-full left-0 mb-2 w-48 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 p-2 overflow-hidden animate-in slide-in-from-bottom-2 z-50">
                        <button type="button" @click="$refs.fileInput.click(); showAttach = false"
                            class="flex items-center gap-3 w-full p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div
                                class="h-8 w-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Document/Media</span>
                        </button>

                        <button type="button" wire:click="openInteractiveButtonsModal" @click="showAttach = false"
                            class="flex items-center gap-3 w-full p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Quick Buttons</span>
                        </button>

                        <button type="button" @click="isNoteMode = !isNoteMode; showAttach = false"
                            class="flex items-center gap-3 w-full p-3 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-left">
                            <div class="h-8 w-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Internal Note</span>
                        </button>
                    </div>
                </div>

                <!-- Emoji Button -->
                <div class="relative">
                    <button type="button" @click="if(!$store.chat.isLockedForMe()) showEmoji = !showEmoji"
                        :disabled="$store.chat.isLockedForMe()"
                        :class="$store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed' : 'hover:text-wa-teal hover:bg-wa-teal/5'"
                        class="p-3 text-slate-400 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <div x-show="showEmoji" @click.away="showEmoji = false" x-cloak
                        class="absolute bottom-full left-0 mb-2 w-64 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 p-3 grid grid-cols-6 gap-1 animate-in slide-in-from-bottom-2 max-h-48 overflow-y-auto custom-scrollbar z-50">
                        @foreach(['😀', '😂', '😍', '😭', '👍', '🙏', '🔥', '🎉', '❤️', '👋', '🤔', '🤝', '✅', '❌', '💪', '✨', '🚫', '⚠️'] as $em)
                            <button type="button" @click="insertEmoji('{{ $em }}')"
                                class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg text-xl transition-colors">{{ $em }}</button>
                        @endforeach
                    </div>
                </div>

                <!-- Input Field -->
                <div class="flex-1 relative group">
                    <!-- AI Draft Button -->
                    <button type="button" wire:click="draftAIResponse" wire:loading.attr="disabled"
                        class="absolute -top-14 right-0 p-3 bg-gradient-to-tr from-wa-teal to-sky-400 text-white rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all text-[10px] font-black uppercase tracking-widest flex items-center gap-2 group/ai overflow-hidden">
                        <!-- AI Particle Effect -->
                        <div
                            class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover/ai:translate-x-[100%] transition-transform duration-700 skew-x-12">
                        </div>

                        <div wire:loading.remove wire:target="draftAIResponse" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 11-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM11 2a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0V6h-1a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span>Draft with AI</span>
                        </div>

                        <div wire:loading wire:target="draftAIResponse" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span>Thinking...</span>
                        </div>
                    </button>

                    <template x-if="isNoteMode">
                        <div
                            class="absolute -top-6 left-6 px-3 py-0.5 bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-400 text-[9px] font-black uppercase tracking-widest rounded-t-lg border-t border-x border-amber-200 dark:border-amber-800">
                            NOTE MODE
                        </div>
                    </template>
                    <textarea x-model="msgBody" @keydown.enter.prevent="handleSubmit" x-ref="messageInput"
                        @focus="$store.chat.requestLock()" @blur="setTimeout(() => $store.chat.releaseLock(), 500)"
                        @keyup="checkQR(); $store.chat.whisperTyping('{{ addslashes(auth()->user()->name ?? 'Agent') }}'); $store.chat.requestLock()"
                        placeholder="Type a message (or / for templates)..." rows="1"
                        :disabled="$store.chat.isLockedForMe()" :class="[
                                                                                                                        $store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed bg-slate-100' : 'bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-wa-teal/20 group-hover:bg-slate-100 dark:group-hover:bg-slate-700/50',
                                                                                                                        isNoteMode ? 'bg-amber-50 dark:bg-amber-900/10 focus:ring-amber-200' : ''
                                                                                                                    ]"
                        class="w-full py-4 px-6 border-none rounded-[2rem] text-sm font-medium placeholder-slate-400 dark:placeholder-slate-600 resize-none max-h-40 transition-all"
                        style="min-height: 56px;"></textarea>

                    <!-- Quick Replies Popover -->
                    <div x-show="showQR" @click.away="showQR = false" x-transition x-cloak
                        class="absolute bottom-full left-0 mb-2 w-full bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden z-50">
                        <div
                            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Quick Replies
                        </div>
                        <div class="max-h-60 overflow-y-auto">
                            <template
                                x-for="qr in quickReplies.filter(q => q.code.toLowerCase().includes(qrFilter) || q.text.toLowerCase().includes(qrFilter))"
                                :key="qr.code">
                                <button type="button" @click="selectQR(qr.text)"
                                    class="w-full text-left px-4 py-3 hover:bg-wa-teal/5 transition-colors border-b border-slate-50 dark:border-slate-800/50 last:border-0 flex flex-col">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                        x-text="'/' + qr.code"></span>
                                    <span class="text-[10px] text-slate-500 truncate" x-text="qr.text"></span>
                                </button>
                            </template>
                            <div x-show="quickReplies.filter(q => q.code.toLowerCase().includes(qrFilter) || q.text.toLowerCase().includes(qrFilter)).length === 0"
                                class="p-4 text-center text-xs text-slate-400 italic">
                                No matching replies...
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="$store.chat.isLockedForMe()"
                    class="h-14 w-14 flex items-center justify-center text-white rounded-[1.5rem] transition-all group"
                    :class="msgBody.trim() || $wire.newAttachment ? 'bg-wa-teal shadow-wa-teal/20' : 'bg-slate-900 shadow-slate-900/10 hover:scale-105 active:scale-95'"
                    wire:loading.attr="disabled">
                    <template x-if="msgBody.trim() || $wire.newAttachment || isNoteMode">
                        <svg class="w-5 h-5 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </template>
                    <template x-if="!msgBody.trim() && !$wire.newAttachment && !isNoteMode">
                        <svg @click.prevent="startRecording()" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                    </template>
                </button>
            </form>
        @else

            <div
                class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] border border-slate-200 dark:border-slate-700 text-center space-y-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-full text-amber-600 dark:text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-black uppercase tracking-wide text-slate-900 dark:text-white mb-1">
                        Session
                        Expired</p>
                    <p class="text-xs font-bold text-slate-500 max-w-xs mx-auto">The 24-hour service window has
                        closed. Use
                        an approved template to re-initiate contact.</p>
                </div>
                <button wire:click="openTemplateList"
                    class="px-6 py-3 bg-wa-teal hover:bg-wa-teal/90 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-lg shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Start New Conversation
                </button>
            </div>
        @endif
    </div>

    <!-- Template List Modal -->
    @if($showTemplateListModal)
        @teleport('body')
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data
            @keydown.escape.window="$wire.closeTemplateModals()">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeTemplateModals"></div>
            <div
                class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="p-8 pb-0 flex justify-between items-center">
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Approved <span class="text-wa-teal">Templates</span>
                    </h2>
                    <button wire:click="closeTemplateModals"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Search -->
                <div class="p-8 pt-4">
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="templateSearch" placeholder="Search templates..."
                            class="w-full px-5 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-wa-teal/20 placeholder:text-slate-400">
                        <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- List - Scrollable Area -->
                <div class="px-8 pb-8 overflow-y-scroll max-h-[400px]"
                    style="scrollbar-width: thin; scrollbar-color: rgb(148 163 184 / 0.5) transparent;">
                    <div class="space-y-3">
                        @forelse($this->filtered_templates as $template)
                            <button wire:click="selectTemplate({{ $template->id }})"
                                class="w-full text-left p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl hover:bg-wa-teal/5 dark:hover:bg-wa-teal/10 transition-colors group border border-slate-100 dark:border-slate-700">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3
                                                class="text-sm font-black text-slate-900 dark:text-white group-hover:text-wa-teal transition-colors truncate">
                                                {{ $template->name }}
                                            </h3>
                                            <span
                                                class="px-2 py-0.5 bg-wa-teal/10 text-wa-teal border border-wa-teal/20 rounded text-[9px] font-black uppercase tracking-widest shrink-0">
                                                {{ $template->status }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">
                                                {{ $template->category }}
                                            </span>
                                            <span class="text-slate-300 dark:text-slate-700">•</span>
                                            <span
                                                class="text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ $template->language }}</span>
                                        </div>
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400 group-hover:text-wa-teal transition-colors shrink-0 mt-1"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </button>
                        @empty
                            <div class="py-12 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800 mb-4">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium text-sm">No templates found matching your search.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif


    <!-- Template Preview Modal -->
    @if($showTemplatePreviewModal && $selectedTemplate)
        @teleport('body')
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm shadow-2xl"
            x-data @keydown.escape.window="$wire.closeTemplateModals()">
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-5xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Template
                            Message</h2>
                        <p class="text-sm font-medium text-slate-500">Review and customize your message before
                            sending.</p>
                    </div>
                    <button wire:click="closeTemplateModals"
                        class="p-2 text-slate-400 hover:text-rose-500 transition-colors bg-slate-50 dark:bg-slate-800 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-2">
                    <!-- Left: Variables Input -->
                    <div class="p-8 overflow-y-scroll max-h-[500px] border-r border-slate-100 dark:border-slate-800">
                        <section class="mb-8">
                            <h3
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Rich Assets
                            </h3>

                            @php
                                $header = $this->getTemplateComponent('HEADER');
                            @endphp

                            @if($this->hasMediaHeader)
                                <div class="space-y-4">
                                    <div class="p-4 bg-wa-teal/5 border border-wa-teal/10 rounded-2xl">
                                        <label class="block text-xs font-black text-wa-teal uppercase tracking-widest mb-2">
                                            {{ $header['format'] }} Header URL
                                        </label>
                                        <input type="url" wire:model.live="templateMediaUrl"
                                            class="w-full px-4 py-3 bg-white dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-wa-teal/20"
                                            placeholder="https://example.com/image.jpg">
                                        <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Link a direct URL
                                            for the {{ strtolower($header['format']) }} header.</p>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="p-6 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 text-center opacity-60">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No Media
                                        Header Required</p>
                                </div>
                            @endif
                        </section>

                        <section>
                            <h3
                                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Message Parameters
                            </h3>

                            <div class="space-y-6">
                                @if(empty($templateVariables))
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl text-center">
                                        <p class="text-sm font-bold text-slate-500">No variables required</p>
                                        <p class="text-xs text-slate-400 mt-1">This protocol contains no dynamic
                                            segments.</p>
                                    </div>
                                @else
                                    @foreach($templateVariables as $key => $value)
                                        <div class="space-y-2">
                                            <label
                                                class="text-xs font-bold text-slate-700 dark:text-slate-300 flex justify-between items-center">
                                                <span>Variable {{ '{' . '{' . $key . '}' . '}' }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono">Slot {{ $key }}</span>
                                            </label>
                                            <input type="text" wire:model.live="templateVariables.{{ $key }}"
                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                                                placeholder="Enter value for {{ '{' . '{' . $key . '}' . '}' }}...">
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </section>
                    </div>

                    <!-- Right: Preview -->
                    <div
                        class="p-8 bg-slate-100 dark:bg-slate-950/50 overflow-y-auto max-h-[500px] flex flex-col items-center justify-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">
                            Transmission Preview</p>

                        <!-- Preview Device Mockup -->
                        <div
                            class="w-full max-w-[320px] bg-white dark:bg-[#0b141a] rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative transform scale-95 transition-transform">
                            <!-- WhatsApp Header Mock -->
                            <div class="bg-[#008069] h-12 w-full flex items-center px-4 gap-3">
                                <div class="w-7 h-7 rounded-full bg-white/20"></div>
                                <div class="h-1.5 w-24 bg-white/20 rounded"></div>
                            </div>

                            <!-- Chat Area -->
                            <div
                                class="p-4 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat min-h-[400px] flex flex-col">

                                <!-- Message Bubble -->
                                <div
                                    class="bg-white dark:bg-[#202c33] p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[95%] self-start relative border border-white dark:border-slate-800">
                                    <!-- Header Media Preview -->
                                    @if($this->hasMediaHeader)
                                        <div class="mb-2 shrink-0">
                                            @if($templateMediaUrl)
                                                @if($header['format'] === 'IMAGE')
                                                    <img src="{{ $templateMediaUrl }}"
                                                        class="w-full aspect-video object-cover rounded-lg shadow-inner">
                                                @else
                                                    <div
                                                        class="w-full aspect-video bg-slate-100 dark:bg-slate-800 rounded-lg flex flex-col items-center justify-center border border-slate-200 dark:border-slate-700">
                                                        <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        <span
                                                            class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $header['format'] }}
                                                            ATTACHED</span>
                                                    </div>
                                                @endif
                                            @else
                                                <div
                                                    class="w-full aspect-video bg-slate-50 dark:bg-slate-800/50 rounded-lg flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-slate-700 opacity-40">
                                                    <span
                                                        class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Awaiting
                                                        Media URL</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <h4
                                        class="font-black text-[10px] text-wa-teal uppercase tracking-widest mb-1 pb-1 border-b border-wa-teal/5">
                                        {{ $selectedTemplate->name }}
                                    </h4>

                                    <p
                                        class="text-[11px] text-slate-800 dark:text-slate-100 whitespace-pre-wrap leading-relaxed">
                                        {{ $this->live_preview_text }}
                                    </p>

                                    <!-- Footer -->
                                    @if($footerComp = $this->getTemplateComponent('FOOTER'))
                                        <p
                                            class="text-[9px] text-slate-400 mt-2 italic border-t border-slate-50 dark:border-slate-800 pt-1">
                                            {{ $footerComp['text'] }}
                                        </p>
                                    @endif

                                    <div class="mt-1.5 flex justify-end">
                                        <span class="text-[8px] text-slate-400">12:00 PM</span>
                                    </div>
                                </div>

                                <!-- Buttons Preview -->
                                @if($buttonComp = $this->getTemplateComponent('BUTTONS'))
                                    <div class="mt-2 space-y-1 w-full max-w-[95%]">
                                        @foreach($buttonComp['buttons'] as $btn)
                                            <div
                                                class="bg-white/90 dark:bg-[#202c33]/90 rounded-xl py-2 px-3 flex items-center justify-center gap-2 border border-white dark:border-slate-800 shadow-sm backdrop-blur-sm">
                                                @if(($btn['type'] ?? '') === 'URL') <svg class="w-3 h-3 text-wa-teal" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                @elseif(($btn['type'] ?? '') === 'PHONE_NUMBER') <svg class="w-3 h-3 text-wa-teal"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                @endif
                                                <span
                                                    class="text-[10px] font-black text-wa-teal uppercase tracking-widest text-center">{{ $btn['text'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-8 py-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-end gap-3">
                    <button wire:click="closeTemplateModals"
                        class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-bold uppercase tracking-widest text-xs rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all">
                        Cancel
                    </button>
                    <button wire:click="sendTemplateWithVariables"
                        class="px-8 py-3 bg-wa-teal hover:bg-wa-teal/90 text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Send Template
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
    <!-- Interactive Buttons Modal -->
    @if($showInteractiveButtonsModal)
        @teleport('body')
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm shadow-2xl"
            x-data @keydown.escape.window="$wire.set('showInteractiveButtonsModal', false)">
            <div
                class="bg-white dark:bg-slate-900 w-full max-w-2xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Quick
                            <span class="text-wa-teal">Buttons</span>
                        </h2>
                        <p class="text-sm font-medium text-slate-500">Send up to 3 interactive reply buttons.</p>
                    </div>
                    <button wire:click="$set('showInteractiveButtonsModal', false)"
                        class="p-2 text-slate-400 hover:text-rose-500 transition-colors bg-slate-50 dark:bg-slate-800 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-2">
                    <!-- Left: Configuration -->
                    <div class="p-8 overflow-y-auto space-y-6 border-r border-slate-100 dark:border-slate-800">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Message
                                Body</label>
                            <textarea wire:model="buttonBody" rows="4"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                                placeholder="Enter your message text..."></textarea>
                            @error('buttonBody') <span class="text-[10px] font-bold text-rose-500">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Buttons
                                    ({{ count($interactiveButtons) }}/3)</label>
                                @if(count($interactiveButtons) < 3)
                                    <button type="button" wire:click="addInteractiveButton"
                                        class="text-xs font-bold text-wa-teal hover:underline">+ Add Button</button>
                                @endif
                            </div>

                            <div class="space-y-3">
                                @foreach($interactiveButtons as $index => $btn)
                                    <div class="flex items-center gap-2 group">
                                        <div class="relative flex-1">
                                            <input type="text" wire:model="interactiveButtons.{{ $index }}" maxlength="20"
                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-wa-teal/20 transition-all font-medium"
                                                placeholder="Button Title">
                                            <span
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-400">
                                                {{ strlen($interactiveButtons[$index] ?? '') }}/20
                                            </span>
                                        </div>
                                        <button type="button" wire:click="removeInteractiveButton({{ $index }})"
                                            class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            @error('interactiveButtons') <span
                                class="text-[10px] font-bold text-rose-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Right: Preview -->
                    <div
                        class="p-8 bg-slate-50/50 dark:bg-slate-950 flex flex-col items-center justify-center border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Live Preview
                        </p>

                        <div
                            class="w-full max-w-[240px] bg-white dark:bg-[#202c33] rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                            <div class="p-3 border-b border-slate-50 dark:border-slate-800/50">
                                <p
                                    class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed break-words whitespace-pre-wrap">
                                    {{ $buttonBody ?: 'Your message text...' }}
                                </p>
                            </div>
                            <div class="flex flex-col">
                                @foreach($interactiveButtons as $btn)
                                    <div
                                        class="py-2.5 px-3 border-b border-slate-50 dark:border-slate-800/50 last:border-0 text-center">
                                        <span class="text-xs font-bold text-wa-teal truncate block">
                                            {{ $btn ?: 'Button Text' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="px-8 py-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 flex justify-end gap-3">
                    <button wire:click="$set('showInteractiveButtonsModal', false)"
                        class="px-6 py-3 text-slate-500 font-bold uppercase tracking-widest text-xs">
                        Cancel
                    </button>
                    <button wire:click="sendInteractiveButtons"
                        class="px-8 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 active:scale-95 transition-all">
                        Send Buttons
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    <!-- Lightbox Modal -->
    <template x-teleport="body">
        <div x-show="$store.chat.lightboxOpen"
            class="fixed inset-0 z-[200] flex items-center justify-center p-4 md:p-10"
            @keydown.escape.window="$store.chat.lightboxOpen = false" x-cloak>

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm shadow-2xl"
                @click="$store.chat.lightboxOpen = false" x-show="$store.chat.lightboxOpen"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            <!-- Modal Content -->
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden"
                x-show="$store.chat.lightboxOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900 z-10">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Image Preview</h3>
                    <div class="flex items-center gap-2">
                        <a :href="$store.chat.lightboxImage" download
                            class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-wa-teal"
                            title="Download">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                        <button @click="$store.chat.lightboxOpen = false"
                            class="p-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl transition-colors text-slate-400 hover:text-rose-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Image Container -->
                <div
                    class="p-6 bg-slate-50/50 dark:bg-slate-950/50 flex items-center justify-center min-h-[300px] max-h-[70vh] overflow-hidden">
                    <img :src="$store.chat.lightboxImage"
                        class="max-w-full max-h-full object-contain rounded-xl shadow-lg animate-in zoom-in duration-300">
                </div>
            </div>
        </div>
    </template>
</div>