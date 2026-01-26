<div class="flex flex-col h-full relative bg-dots-pattern" 
    x-data="{ 
        isTyping: false,
        typingUser: '',
        activeUsers: [],
        pChannel: null,
        lightboxOpen: false,
        lightboxImage: '',
        init() {
            // Init Store User ID
            $store.chat.setMyUser({{ auth()->id() }});
            
            // --- Presence Channel (Multi-Agent) ---
            console.log('Front: Joining presence-conversation.{{ $conversationId }}');
            this.pChannel = window.Echo.join('conversation.{{ $conversationId }}');
            
            this.pChannel.here((users) => {
                this.activeUsers = users;
            })
            .joining((user) => {
                this.activeUsers.push(user);
                console.log(user.name + ' joined.');
            })
            .leaving((user) => {
                this.activeUsers = this.activeUsers.filter(u => u.id !== user.id);
            })
            .listen('.MessageReceived', (e) => {
                console.log('Front (pChannel): MessageReceived', e);
                $store.chat.syncLatest();
            })
            .listen('.MessageStatusUpdated', (e) => {
                 console.log('Front (pChannel): MessageStatusUpdated', e);
                 if(e.message) {
                     let msg = $store.chat.messages.find(m => m.id === e.message.id);
                     if(msg) msg.status = e.message.status;
                 }
            })
            .listenForWhisper('typing', (e) => {
                if (e.id !== {{ auth()->id() }}) {
                    this.isTyping = true;
                    this.typingUser = e.name;
                    if (this.typingTimer) clearTimeout(this.typingTimer);
                    this.typingTimer = setTimeout(() => this.isTyping = false, 3000);
                    $store.chat.setLockState(e.id);
                }
            });

            // --- Team Events (Backup) ---
            const channel = window.Echo.private('teams.{{ auth()->user()->currentTeam->id }}');
            
            channel.listen('.MessageReceived', (e) => { 
                console.log('Front (Team): MessageReceived', e);
                if (e.message && e.message.conversation_id == {{ $conversationId }}) {
                    $store.chat.syncLatest(); 
                }
            });

            channel.listen('.MessageStatusUpdated', (e) => {
                 console.log('Front (Team): MessageStatusUpdated', e);
                 if (e.message) {
                     let msg = $store.chat.messages.find(m => m.id === e.message.id);
                     if (msg) msg.status = e.message.status;
                 }
            });

            // Sync on Reconnect
            window.addEventListener('online', () => {
                $store.chat.setConnectionState('connected');
            });
            window.addEventListener('offline', () => {
                $store.chat.setConnectionState('offline');
            });

            window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                // states = { previous: 'old', current: 'new' }
                // map pulsar states: connected, connecting, unavailable, failed, disconnected
                if (states.current === 'connected') {
                     $store.chat.setConnectionState('connected');
                } else if (states.current === 'connecting') {
                     $store.chat.setConnectionState('connecting');
                } else {
                     $store.chat.setConnectionState('offline');
                }
            });
        }
    }"
    @play-sound.window="
        document.getElementById('notification-sound').play().catch(error => {
            console.log('Audio autoplay blocked until user interaction:', error);
        });
        if (Notification.permission === 'granted') {
            new Notification('New Message', { body: 'You have received a new message.', icon: '/favicon.ico' });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    ">
    <audio id="notification-sound" src="https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3" preload="auto"></audio>
    
    <!-- Connection Status Banner -->
    <div x-show="$store.chat.connectionState !== 'connected'" x-cloak x-transition
         class="w-full z-40 bg-rose-500/10 dark:bg-rose-900/20 py-2 border-b border-rose-200 dark:border-rose-900/50 flex items-center justify-center">
        <div class="text-rose-600 dark:text-rose-400 text-xs font-bold inline-flex items-center gap-2">
             <template x-if="$store.chat.connectionState === 'connecting'">
                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
             </template>
             <template x-if="$store.chat.connectionState === 'offline'">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
             </template>
             <span x-text="$store.chat.connectionState === 'connecting' ? 'Reconnecting to chat...' : 'You are currently offline'"></span>
        </div>
    </div>

    <!-- Header -->
    <div
        class="px-6 py-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-100 dark:border-slate-800 flex justify-between items-center z-10 sticky top-0 shadow-sm">
        <div class="flex items-center">
            <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $conversation->contact->name ?? 'Unknown' }}"
                alt="{{ $conversation->contact->name ?? 'Unknown' }}"
                class="h-10 w-10 rounded-xl object-cover bg-slate-100 dark:bg-slate-800 shadow-lg shadow-wa-teal/20 mr-4">
            <div>
                <h2
                    class="font-black text-slate-900 dark:text-white tracking-tight leading-none uppercase text-sm mb-0.5">
                    {{ $conversation->contact->name ?? $conversation->contact->phone_number }}
                </h2>
                <div class="text-[10px] font-bold text-slate-500 flex items-center gap-2 uppercase tracking-wide">
                    <span class="text-wa-teal">{{ $conversation->contact->phone_number }}</span>
                    
                    <span x-show="isTyping" x-transition class="text-wa-teal animate-pulse font-black flex items-center gap-1">
                        <span x-text="typingUser"></span> IS TYPING...
                    </span>

                    @if($conversation->contact->is_bot_paused)
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <span class="flex items-center gap-1 text-rose-500 font-black">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            BOT PAUSED
                            @if($conversation->contact->bot_paused_reason)
                                <span class="text-[8px] opacity-60">({{ $conversation->contact->bot_paused_reason }})</span>
                            @endif
                        </span>
                    @else
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <span class="flex items-center gap-1 text-emerald-500 font-black">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            BOT ACTIVE
                        </span>
                    @endif

                    @if($conversation->last_message_at)
                        <span class="text-slate-300 dark:text-slate-700" x-show="!isTyping">|</span>
                        <span class="{{ $conversation->last_message_at->diffInHours() > 24 ? 'text-rose-500' : '' }}" x-show="!isTyping">
                            {{ $conversation->last_message_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Multi-Agent Presence & Actions -->
        <div class="flex items-center gap-4">
            <!-- Presence Pile -->
            <div class="flex -space-x-2 overflow-hidden">
                <template x-for="user in activeUsers" :key="user.id">
                     <div class="relative group/avatar cursor-help">
                        <img :src="user.profile_photo_url || `https://ui-avatars.com/api/?name=${user.name}&background=random`" 
                             :alt="user.name"
                             class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-slate-900"
                             :title="user.name">
                        <div class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-green-500 border-2 border-white dark:border-slate-900"></div>
                     </div>
                 </template>
            </div>
            
            <div class="flex items-center gap-3" x-data="{ showCloseModal: false }">
                <button @click="$dispatch('toggle-details')"
                    class="hidden xl:flex p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-wa-teal"
                    title="Toggle Contact Info">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                <button wire:click="toggleBot"
                    class="p-2 rounded-xl transition-all {{ $conversation->contact->is_bot_paused ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-500' }} hover:scale-105"
                    title="{{ $conversation->contact->is_bot_paused ? 'Resume Bot' : 'Pause Bot' }}">
                    @if($conversation->contact->is_bot_paused)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @endif
                </button>

                <span
                    class="px-2 py-1 text-[9px] font-black rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-400 uppercase tracking-widest">{{ $conversation->status }}</span>

                @if($conversation->status !== 'closed')
                    <button @click="showCloseModal = !showCloseModal"
                        class="p-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl transition-colors text-slate-400 hover:text-rose-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
    </div>

    <!-- DEBUG INDICATOR (Remove after fixing) -->
    <div class="bg-red-500 text-white text-xs font-bold p-1 text-center z-50">
        DEBUG: Messages in Store: <span x-text="$store.chat.messages.length"></span> | 
        Viewport: <span x-text="viewportHeight"></span> | 
        Render: <span x-text="renderConfig.start"></span>-<span x-text="renderConfig.end"></span>
    </div>

    <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50 dark:bg-slate-950 relative" 
         id="messages-container"
         x-data="{
            itemHeight: 80, // Average height estimate
            buffer: 5,
            viewportHeight: 0,
            scrollTop: 0,
             init() {
                $store.chat.init($wire, {{ $conversationId }});
                this.viewportHeight = this.$el.clientHeight;
                
                // Debug logs
                console.log('MessageWindow: Init', { 
                    convId: {{ $conversationId }}, 
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
         }"
         @scroll.passive="handleScroll"
         x-init="init()"
    >

        <div class="flex justify-center mb-8" :style="{ marginTop: renderConfig.top + 'px' }">
             <span class="px-4 py-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-[9px] font-bold text-amber-700 dark:text-amber-400 tracking-wide border border-amber-200 dark:border-amber-800 flex items-center gap-2 shadow-sm">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
                Messages are end-to-end encrypted
            </span>
        </div>

        <template x-for="message in visibleMessages" :key="message.id">
            <div class="w-full">
            <!-- Call Log Entry -->
            <template x-if="message.type === 'call_log'">
                <div class="flex justify-center mb-8 px-4">
                    <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/20 overflow-hidden group/call">
                        <!-- Card Header -->
                        <div class="p-5 flex items-center justify-between border-b border-slate-50 dark:border-slate-800/50">
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-2xl" :class="message.metadata?.status === 'completed' ? 'bg-wa-teal/10 text-wa-teal' : 'bg-rose-500/10 text-rose-500'">
                                    <template x-if="message.metadata?.status === 'completed'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2 2m-2-2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h2M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-.311-.104a4.501 4.501 0 00-1.456-.272H10.16c-.445 0-.882.044-1.308.13l-1.488.3a4.502 4.502 0 01-1.456.272H5.16c-.618 0-1.217-.247-1.605-.729a11.95 11.95 0 01-2.649-7.521c0-.435.023-.863.068-1.285.109-1.021 1.028-1.715 2.054-1.715h3.605z" /></svg>
                                    </template>
                                    <template x-if="message.metadata?.status !== 'completed'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2 2m-2-2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h2M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-.311-.104a4.501 4.501 0 00-1.456-.272H10.16c-.445 0-.882.044-1.308.13l-1.488.3a4.502 4.502 0 01-1.456.272H5.16c-.618 0-1.217-.247-1.605-.729a11.95 11.95 0 01-2.649-7.521c0-.435.023-.863.068-1.285.109-1.021 1.028-1.715 2.054-1.715h3.605z" /></svg>
                                    </template>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400" x-text="message.metadata?.status === 'completed' ? 'VoIP Session' : 'Missed Interaction'"></span>
                                    <span class="text-sm font-black text-slate-900 dark:text-white" x-text="message.content"></span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400" x-text="message.pretty_time"></span>
                        </div>

                        <!-- Card Body (Post-Call Actions & Notes) -->
                        <div class="p-5 space-y-4" x-data="{ editingNote: false, noteValue: message.metadata?.agent_note || '' }">
                            <!-- Notes Display/Input -->
                            <div class="relative">
                                <template x-if="!editingNote && !noteValue">
                                    <button @click="editingNote = true" class="w-full py-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 text-[10px] font-black text-slate-400 uppercase tracking-[0.1em] hover:bg-slate-100 transition-all flex items-center justify-center gap-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Add Summary Note
                                    </button>
                                </template>
                                <template x-if="!editingNote && noteValue">
                                    <div class="p-4 bg-amber-50/50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/20 relative group/note cursor-pointer" @click="editingNote = true">
                                        <span class="text-xs font-medium text-amber-900 dark:text-amber-300 leading-relaxed" x-text="noteValue"></span>
                                        <div class="absolute inset-0 bg-white/40 dark:bg-black/40 backdrop-blur-[1px] opacity-0 group-hover/note:opacity-100 transition-opacity flex items-center justify-center rounded-2xl">
                                             <span class="text-[9px] font-black text-slate-900 dark:text-white uppercase">Click to edit</span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="editingNote">
                                    <div class="space-y-2">
                                        <textarea x-model="noteValue" class="w-full p-4 bg-white dark:bg-slate-800 border-2 border-wa-teal/20 rounded-2xl text-xs font-medium focus:ring-0 focus:border-wa-teal transition-all" rows="3" placeholder="Summarize the outcome..."></textarea>
                                        <div class="flex justify-end gap-2">
                                            <button @click="editingNote = false" class="px-4 py-2 text-[10px] font-black text-slate-400 uppercase">Cancel</button>
                                            <button @click="$wire.saveCallNote(message.id, noteValue); editingNote = false; message.metadata.agent_note = noteValue" class="px-4 py-2 bg-wa-teal text-white rounded-xl text-[10px] font-black uppercase">Save Note</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Suggested Actions -->
                            <div class="pt-2 flex flex-wrap gap-2">
                                <template x-if="message.metadata?.status !== 'completed'">
                                    <button @click="$wire.openTemplateList()" class="px-3 py-2 bg-wa-teal/10 text-wa-teal hover:bg-wa-teal text-[9px] font-black uppercase tracking-widest rounded-xl hover:text-white transition-all border border-wa-teal/20">
                                        🚀 Send Follow-up
                                    </button>
                                </template>
                                <button class="px-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all border border-slate-200 dark:border-slate-700">
                                    🗓️ Schedule Task
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Standard Message Bubble -->
            <template x-if="message.type !== 'call_log'">
                <div :class="['flex', message.is_outbound ? 'justify-end' : 'justify-start', 'mb-6']">
                    <div class="max-w-[85%] sm:max-w-[70%] group">
                        <div :class="[
                            'relative p-3 px-4 transition-all hover:scale-[1.01] shadow-sm',
                            message.is_outbound 
                                ? 'bg-wa-teal text-white rounded-2xl rounded-tr-sm shadow-xl shadow-wa-teal/10' 
                                : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-sm border border-slate-100 dark:border-slate-700'
                        ]">
                            <!-- Attribution Badge -->
                            <template x-if="message.attributed_campaign_name">
                                <div class="mb-2 flex items-center gap-1.5 px-2 py-1 rounded-lg bg-wa-teal/10 dark:bg-wa-teal/20 border border-wa-teal/20">
                                    <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167a2.405 2.405 0 011.002-2.736l3.144-1.921A1.76 1.76 0 0111 5.882zM15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span class="text-[9px] font-black text-wa-teal uppercase tracking-widest">
                                        Reply to: <span x-text="message.attributed_campaign_name"></span>
                                    </span>
                                </div>
                            </template>
    
                            <!-- Media -->
                            <template x-if="message.media_url">
                                <div class="mb-3 rounded-lg overflow-hidden border border-white/10">
                                    <template x-if="message.media_type && message.media_type.startsWith('image')">
                                        <img :src="message.media_url" class="w-full max-h-80 object-cover cursor-pointer hover:opacity-90 rounded-lg shadow-sm" @click="lightboxImage = message.media_url; lightboxOpen = true">
                                    </template>
                                    <template x-if="message.media_type && message.media_type.startsWith('video')">
                                        <video :src="message.media_url" controls class="w-full max-h-80"></video>
                                    </template>
                                    <template x-if="message.media_type && message.media_type.startsWith('audio')">
                                        <audio :src="message.media_url" controls class="w-full"></audio>
                                    </template>
                                    <template x-if="message.media_type && !['image','video','audio'].some(t => message.media_type.startsWith(t))">
                                        <a :href="message.media_url" target="_blank" class="flex items-center gap-3 p-3 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            <span class="font-bold text-xs truncate">Document</span>
                                        </a>
                                    </template>
                                </div>
                            </template>
    
                            <!-- Text -->
                            <template x-if="message.content && message.content !== '[Image]'">
                                <p class="text-xs sm:text-sm font-medium whitespace-pre-wrap leading-relaxed" x-text="message.content"></p>
                            </template>
                            
                            <!-- Caption -->
                            <template x-if="message.caption && !message.content">
                                 <p class="text-xs font-bold italic opacity-80 mt-1" x-text="message.caption"></p>
                            </template>
    
                            <!-- Metadata -->
                            <div class="text-[9px] font-black uppercase tracking-widest mt-2 flex items-center justify-end gap-1.5 opacity-60">
                                <span x-text="message.pretty_time"></span>
                                
                                <template x-if="message.is_outbound">
                                    <span>
                                        <template x-if="message.status === 'read'">
                                            <svg class="w-3 h-3 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7M5 7l4 4 10-10" /></svg>
                                        </template>
                                        <template x-if="message.status === 'delivered'">
                                            <svg class="w-3 h-3 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7M5 7l4 4 10-10" /></svg>
                                        </template>
                                        <template x-if="message.status === 'sent'">
                                            <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </template>
                                        <template x-if="message.status === 'failed'">
                                            <div class="group/error relative">
                                                <svg class="w-3 h-3 text-rose-300 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                        </template>
                                        <template x-if="['queued', 'sending'].includes(message.status)">
                                            <svg class="w-3 h-3 text-white/40 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </template>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <div :style="{ height: renderConfig.bottom + 'px' }"></div>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-t border-slate-100 dark:border-slate-800 z-10"
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
            }
         }">

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
                        <div>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                {{ $newAttachment->getClientOriginalName() }}
                            </p>
                            <p class="text-[10px] text-slate-500">{{ number_format($newAttachment->getSize() / 1024, 1) }} KB
                            </p>
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

            <form @submit.prevent="handleSubmit" class="flex items-center gap-2 relative">

                <!-- Lock Banner -->
                <div x-show="$store.chat.isLockedForMe()" x-transition x-cloak
                     class="absolute bottom-full left-0 w-full mb-4 p-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs z-20">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        <span class="font-bold text-slate-500">
                            Reply Locked: <span class="text-slate-800 dark:text-slate-200" x-text="$store.chat.lockedBy ? $store.chat.lockedBy.name : 'Another Agent'"></span> is writing...
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
                     <textarea x-model="msgBody" @keydown.enter.prevent="handleSubmit" x-ref="messageInput"
                        @focus="$store.chat.requestLock()"
                        @blur="setTimeout(() => $store.chat.releaseLock(), 500)"
                        @keyup="checkQR(); pChannel.whisper('typing', { conversation_id: {{ $conversationId }}, name: '{{ auth()->user()->name }}', id: {{ auth()->id() }} }); $store.chat.requestLock()" 
                        placeholder="Type a message (or / for templates)..." rows="1"
                        :disabled="$store.chat.isLockedForMe()"
                        :class="$store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed bg-slate-100' : 'bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-wa-teal/20 group-hover:bg-slate-100 dark:group-hover:bg-slate-700/50'"
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

                <button type="submit"
                    :disabled="$store.chat.isLockedForMe()"
                    :class="$store.chat.isLockedForMe() ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-slate-900 dark:bg-wa-teal hover:scale-105 active:scale-95 shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20'"
                    class="h-14 w-14 flex items-center justify-center text-white rounded-[1.5rem] transition-all group"
                    wire:loading.attr="disabled">
                    <svg class="w-5 h-5 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
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
                    <p class="text-sm font-black uppercase tracking-wide text-slate-900 dark:text-white mb-1">Session
                        Expired</p>
                    <p class="text-xs font-bold text-slate-500 max-w-xs mx-auto">The 24-hour service window has closed. Use
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
                                <p class="text-slate-500 font-medium text-sm">No templates found matching your search.</p>
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
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Template Message</h2>
                        <p class="text-sm font-medium text-slate-500">Review and customize your message before sending.</p>
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
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
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
                                        <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Link a direct URL for the {{ strtolower($header['format']) }} header.</p>
                                    </div>
                                </div>
                            @else
                                <div class="p-6 bg-slate-50 dark:bg-slate-800/30 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 text-center opacity-60">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No Media Header Required</p>
                                </div>
                            @endif
                        </section>

                        <section>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Message Parameters
                            </h3>

                            <div class="space-y-6">
                                @if(empty($templateVariables))
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl text-center">
                                        <p class="text-sm font-bold text-slate-500">No variables required</p>
                                        <p class="text-xs text-slate-400 mt-1">This protocol contains no dynamic segments.</p>
                                    </div>
                                @else
                                    @foreach($templateVariables as $key => $value)
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-slate-700 dark:text-slate-300 flex justify-between items-center">
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
                    <div class="p-8 bg-slate-100 dark:bg-slate-950/50 overflow-y-auto max-h-[500px] flex flex-col items-center justify-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-8">Transmission Preview</p>
                        
                        <!-- Preview Device Mockup -->
                        <div class="w-full max-w-[320px] bg-white dark:bg-[#0b141a] rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative transform scale-95 transition-transform">
                            <!-- WhatsApp Header Mock -->
                            <div class="bg-[#008069] h-12 w-full flex items-center px-4 gap-3">
                                <div class="w-7 h-7 rounded-full bg-white/20"></div>
                                <div class="h-1.5 w-24 bg-white/20 rounded"></div>
                            </div>

                            <!-- Chat Area -->
                            <div class="p-4 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat min-h-[400px] flex flex-col">
                                
                                <!-- Message Bubble -->
                                <div class="bg-white dark:bg-[#202c33] p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[95%] self-start relative border border-white dark:border-slate-800">
                                    <!-- Header Media Preview -->
                                    @if($this->hasMediaHeader)
                                        <div class="mb-2 shrink-0">
                                            @if($templateMediaUrl)
                                                @if($header['format'] === 'IMAGE')
                                                    <img src="{{ $templateMediaUrl }}" class="w-full aspect-video object-cover rounded-lg shadow-inner">
                                                @else
                                                    <div class="w-full aspect-video bg-slate-100 dark:bg-slate-800 rounded-lg flex flex-col items-center justify-center border border-slate-200 dark:border-slate-700">
                                                        <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $header['format'] }} ATTACHED</span>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="w-full aspect-video bg-slate-50 dark:bg-slate-800/50 rounded-lg flex flex-col items-center justify-center border border-dashed border-slate-200 dark:border-slate-700 opacity-40">
                                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Awaiting Media URL</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <h4 class="font-black text-[10px] text-wa-teal uppercase tracking-widest mb-1 pb-1 border-b border-wa-teal/5">
                                        {{ $selectedTemplate->name }}
                                    </h4>

                                    <p class="text-[11px] text-slate-800 dark:text-slate-100 whitespace-pre-wrap leading-relaxed">
                                        {{ $this->live_preview_text }}
                                    </p>

                                    <!-- Footer -->
                                    @if($footerComp = $this->getTemplateComponent('FOOTER'))
                                        <p class="text-[9px] text-slate-400 mt-2 italic border-t border-slate-50 dark:border-slate-800 pt-1">{{ $footerComp['text'] }}</p>
                                    @endif

                                    <div class="mt-1.5 flex justify-end">
                                        <span class="text-[8px] text-slate-400">12:00 PM</span>
                                    </div>
                                </div>

                                <!-- Buttons Preview -->
                                @if($buttonComp = $this->getTemplateComponent('BUTTONS'))
                                    <div class="mt-2 space-y-1 w-full max-w-[95%]">
                                        @foreach($buttonComp['buttons'] as $btn)
                                            <div class="bg-white/90 dark:bg-[#202c33]/90 rounded-xl py-2 px-3 flex items-center justify-center gap-2 border border-white dark:border-slate-800 shadow-sm backdrop-blur-sm">
                                                @if(($btn['type'] ?? '') === 'URL') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                @elseif(($btn['type'] ?? '') === 'PHONE_NUMBER') <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                @endif
                                                <span class="text-[10px] font-black text-wa-teal uppercase tracking-widest text-center">{{ $btn['text'] }}</span>
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
                        <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Quick <span
                                class="text-wa-teal">Buttons</span></h2>
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
                            class="text-[10px] font-bold text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Right: Preview -->
                    <div
                        class="p-8 bg-slate-50/50 dark:bg-slate-950 flex flex-col items-center justify-center border border-slate-100 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Live Preview</p>

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
        <div x-show="lightboxOpen" 
             class="fixed inset-0 z-[200] flex items-center justify-center p-4 md:p-10"
             @keydown.escape.window="lightboxOpen = false"
             x-cloak>
            
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm shadow-2xl" @click="lightboxOpen = false"
                 x-show="lightboxOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>
            
            <!-- Modal Content -->
            <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden"
                 x-show="lightboxOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900 z-10">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Image Preview</h3>
                    <div class="flex items-center gap-2">
                        <a :href="lightboxImage" download 
                           class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-wa-teal"
                           title="Download">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                        <button @click="lightboxOpen = false" 
                                class="p-2 hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl transition-colors text-slate-400 hover:text-rose-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Image Container -->
                <div class="p-6 bg-slate-50/50 dark:bg-slate-950/50 flex items-center justify-center min-h-[300px] max-h-[70vh] overflow-hidden">
                    <img :src="lightboxImage" 
                         class="max-w-full max-h-full object-contain rounded-xl shadow-lg animate-in zoom-in duration-300">
                </div>
            </div>
        </div>
    </template>
</div>