<div class="flex flex-col h-full relative bg-dots-pattern" 
    x-data="{ 
        isTyping: false,
        typingUser: '',
        init() {
            window.Echo.private('teams.{{ auth()->user()->currentTeam->id }}')
                .listenForWhisper('typing', (e) => {
                    if(e.conversation_id == {{ $conversationId }}) {
                        this.isTyping = true;
                        this.typingUser = e.name;
                        setTimeout(() => this.isTyping = false, 3000);
                    }
                });
        }
    }"
    @play-sound.window="document.getElementById('notification-sound').play()">
    <audio id="notification-sound" src="https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3" preload="auto"></audio>
    <!-- Header -->
    <div
        class="px-6 py-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-100 dark:border-slate-800 flex justify-between items-center z-10 sticky top-0">
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
                    <span x-show="isTyping" x-transition class="text-wa-teal animate-pulse font-black" style="display: none;">
                        • TYPING...
                    </span>
                    @if($conversation->last_message_at)
                        <span class="text-slate-300 dark:text-slate-700">|</span>
                        <span class="{{ $conversation->last_message_at->diffInHours() > 24 ? 'text-rose-500' : '' }}">
                            {{ $conversation->last_message_at->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
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

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50/50 dark:bg-slate-950" id="messages-container" x-data
        x-init="$el.scrollTop = $el.scrollHeight; setTimeout(() => $el.scrollTop = $el.scrollHeight, 100)"
        @scroll-bottom.window="$el.scrollTop = $el.scrollHeight">

        <div class="flex justify-center mb-8">
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

        @foreach($conversation->messages as $message)
            <div
                class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }} animate-in slide-in-from-bottom-2 duration-300">
                <div class="max-w-[85%] sm:max-w-[70%] group">
                    <div
                        class="relative {{ $message->direction === 'outbound'
            ? 'bg-wa-teal text-white rounded-2xl rounded-tr-sm shadow-xl shadow-wa-teal/10'
            : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-sm shadow-sm border border-slate-100 dark:border-slate-700' }} 
                                                                                p-3 px-4 transition-all hover:scale-[1.01]">

                        <!-- Media -->
                        @if($message->media_url)
                            <div class="mb-3 rounded-lg overflow-hidden border border-white/10">
                                @if(Str::startsWith($message->media_type, 'image'))
                                    <img src="{{ Storage::url($message->media_url) }}"
                                        class="w-full max-h-80 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                                        onclick="window.open(this.src)">
                                @elseif(Str::startsWith($message->media_type, 'video'))
                                    <video src="{{ Storage::url($message->media_url) }}" controls class="w-full max-h-80"></video>
                                @elseif(Str::startsWith($message->media_type, 'audio'))
                                    <audio src="{{ Storage::url($message->media_url) }}" controls class="w-full"></audio>
                                @else
                                    <a href="{{ Storage::url($message->media_url) }}" target="_blank"
                                        class="flex items-center gap-3 p-3 bg-white/10 rounded-lg border border-white/10 hover:bg-white/20 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="font-bold text-xs truncate">Document</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        <!-- Text -->
                        @if($message->content)
                            <p class="text-xs sm:text-sm font-medium whitespace-pre-wrap leading-relaxed">
                                {{ $message->content }}
                            </p>
                        @endif

                        <!-- Caption -->
                        @if($message->caption && !$message->content)
                            <p class="text-xs font-bold italic opacity-80 mt-1">{{ $message->caption }}</p>
                        @endif

                        <!-- Metadata -->
                        <div
                            class="text-[9px] font-black uppercase tracking-widest mt-2 flex items-center justify-end gap-1.5 opacity-60">
                            <span>{{ $message->created_at->format('H:i') }}</span>
                            @if($message->direction === 'outbound')
                                @if($message->status === 'read')
                                    <!-- Read: Blue Double Tick -->
                                    <svg class="w-3 h-3 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7M5 7l4 4 10-10" />
                                    </svg>
                                @elseif($message->status === 'delivered')
                                    <!-- Delivered: Gray Double Tick -->
                                    <svg class="w-3 h-3 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7M5 7l4 4 10-10" />
                                    </svg>
                                @elseif($message->status === 'sent')
                                    <!-- Sent: Single Gray Tick -->
                                    <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                @elseif($message->status === 'failed')
                                    <!-- Failed: Red Exclamation with Tooltip -->
                                    <div class="group/error relative">
                                        <svg class="w-3 h-3 text-rose-300 cursor-help" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        @if($message->error_message)
                                            <div
                                                class="absolute bottom-full right-0 mb-2 w-48 p-2 bg-rose-900/90 text-white text-[9px] rounded-lg shadow-xl invisible group-hover/error:visible z-50 whitespace-normal normal-case">
                                                {{ $message->error_message }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- Queued/Pending: Clock -->
                                    <svg class="w-3 h-3 text-white/40 animate-pulse" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-t border-slate-100 dark:border-slate-800 z-10"
        x-data="{ 
            showAttach: false, 
            showEmoji: false, 
            showQR: false,
            qrFilter: '',
            quickReplies: {{ \Illuminate\Support\Js::from($quickReplies) }},
            checkQR() {
                const val = $wire.get('messageBody') || '';
                const match = val.match(/\/(.*)$/);
                if (match) {
                    this.showQR = true;
                    this.qrFilter = match[1].toLowerCase();
                } else {
                    this.showQR = false;
                }
            },
            selectQR(text) {
                const val = $wire.get('messageBody') || '';
                const newVal = val.replace(/\/(.*)$/, text);
                $wire.set('messageBody', newVal);
                this.showQR = false;
                $refs.messageInput.focus();
            },
            insertEmoji(emoji) {
                $wire.set('messageBody', ($wire.get('messageBody') || '') + emoji);
                this.showEmoji = false;
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

            <form wire:submit.prevent="sendMessage" class="flex items-center gap-2 relative">

                <!-- Hidden File Input -->
                <input type="file" wire:model="newAttachment" class="hidden" x-ref="fileInput"
                    accept="image/*,video/*,audio/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">

                <!-- Attach Button (Popover) -->
                <div class="relative">
                    <button type="button" @click="showAttach = !showAttach"
                        class="p-3 text-slate-400 hover:text-wa-teal hover:bg-wa-teal/5 rounded-xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>

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
                    <button type="button" @click="showEmoji = !showEmoji"
                        class="p-3 text-slate-400 hover:text-wa-teal hover:bg-wa-teal/5 rounded-xl transition-all">
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
                     <textarea wire:model="messageBody" wire:keydown.enter.prevent="sendMessage" x-ref="messageInput"
                        @keyup="checkQR(); window.Echo.private('teams.{{ auth()->user()->currentTeam->id }}').whisper('typing', { conversation_id: {{ $conversationId }}, name: 'Agent' })" placeholder="Type a message (or / for templates)..." rows="1"
                        class="w-full py-4 px-6 bg-slate-50 dark:bg-slate-800 border-none focus:ring-2 focus:ring-wa-teal/20 rounded-[2rem] text-sm font-medium placeholder-slate-400 dark:placeholder-slate-600 resize-none max-h-40 transition-all group-hover:bg-slate-100 dark:group-hover:bg-slate-700/50"
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
                    class="h-14 w-14 flex items-center justify-center bg-slate-900 dark:bg-wa-teal text-white rounded-[1.5rem] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 disabled:opacity-50 group"
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
</div>