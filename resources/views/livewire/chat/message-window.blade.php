<div class="flex flex-col h-full relative bg-slate-100 dark:bg-slate-950">
    <!-- Header -->
    <div class="px-6 py-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex justify-between items-center z-10 sticky top-0">
        <div class="flex items-center">
            <div class="h-12 w-12 rounded-2xl bg-wa-green/10 flex items-center justify-center text-wa-green font-black mr-4 shadow-sm border border-wa-green/20">
                {{ substr($conversation->contact->name ?? '?', 0, 1) }}
            </div>
            <div>
                <h2 class="font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none">{{ $conversation->contact->name ?? 'Unknown' }}</h2>
                <div class="text-xs font-bold text-slate-500 mt-1 flex items-center gap-2">
                    <span class="text-wa-green">{{ $conversation->contact->phone_number }}</span>
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
            <span class="px-2 py-1 text-[10px] font-black rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-500 uppercase tracking-widest">{{ $conversation->status }}</span>

            @if($conversation->status !== 'closed')
                <button @click="showCloseModal = !showCloseModal"
                    class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-rose-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            @endif

            <!-- Modal -->
            <div x-show="showCloseModal" x-cloak
                class="absolute top-16 right-6 bg-white dark:bg-slate-800 shadow-2xl border border-slate-200 dark:border-slate-700 rounded-2xl p-4 z-50 w-56 animate-in fade-in zoom-in duration-200"
                @click.away="showCloseModal = false">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Close Conversation</p>
                <div class="grid grid-cols-1 gap-2">
                    <button wire:click="closeConversation('resolved')" @click="showCloseModal = false"
                        class="flex items-center px-3 py-2 text-xs font-bold hover:bg-emerald-50 dark:hover:bg-emerald-950/30 rounded-xl text-emerald-600 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span> Resolved
                    </button>
                    <button wire:click="closeConversation('spam')" @click="showCloseModal = false"
                        class="flex items-center px-3 py-2 text-xs font-bold hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-xl text-rose-600 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-rose-500 mr-2"></span> Spam
                    </button>
                    <button wire:click="closeConversation('timeout')" @click="showCloseModal = false"
                        class="flex items-center px-3 py-2 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl text-slate-600 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-slate-400 mr-2"></span> No Response
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6 wa-bg-pattern dark:bg-none dark:bg-slate-950" id="messages-container" x-data
        x-init="$el.scrollTop = $el.scrollHeight; setTimeout(() => $el.scrollTop = $el.scrollHeight, 100)" @scroll-bottom.window="$el.scrollTop = $el.scrollHeight">
        
        <div class="flex justify-center mb-8">
            <span class="px-4 py-1.5 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-full text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] shadow-sm border border-slate-100 dark:border-slate-800">
                Encryption Enabled
            </span>
        </div>

        @foreach($conversation->messages as $message)
            <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }} animate-in slide-in-from-bottom-2 duration-300">
                <div class="max-w-[85%] sm:max-w-[70%] group">
                    <div class="relative {{ $message->direction === 'outbound' 
                        ? 'bg-wa-teal text-white rounded-2xl rounded-tr-none shadow-lg' 
                        : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-none shadow-md border border-slate-100 dark:border-slate-700' }} 
                        p-3 px-4 transition-all hover:shadow-xl">
                        
                        <!-- Media -->
                        @if($message->media_url)
                            <div class="mb-3 rounded-xl overflow-hidden shadow-inner">
                                @if(Str::startsWith($message->media_type, 'image'))
                                    <img src="{{ Storage::url($message->media_url) }}"
                                        class="w-full max-h-80 object-cover cursor-pointer hover:scale-105 transition-transform duration-500"
                                        onclick="window.open(this.src)">
                                @elseif(Str::startsWith($message->media_type, 'video'))
                                    <video src="{{ Storage::url($message->media_url) }}" controls class="w-full max-h-80"></video>
                                @elseif(Str::startsWith($message->media_type, 'audio'))
                                    <audio src="{{ Storage::url($message->media_url) }}" controls class="w-full"></audio>
                                @else
                                    <a href="{{ Storage::url($message->media_url) }}" target="_blank"
                                        class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 text-wa-blue hover:text-wa-teal transition-colors">
                                        <div class="p-2 bg-wa-blue/10 rounded-lg">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <span class="font-bold text-sm truncate">Attachment Download</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        <!-- Text -->
                        @if($message->content)
                            <p class="text-sm font-medium whitespace-pre-wrap leading-relaxed">{{ $message->content }}</p>
                        @endif

                        <!-- Caption -->
                        @if($message->caption && !$message->content)
                            <p class="text-xs font-bold italic opacity-80 mt-1">{{ $message->caption }}</p>
                        @endif

                        <!-- Metadata -->
                        <div class="text-[9px] font-black uppercase tracking-widest mt-2 flex items-center justify-end gap-1.5 opacity-60">
                            <span>{{ $message->created_at->format('H:i') }}</span>
                            @if($message->direction === 'outbound')
                                @if($message->status === 'read')
                                    <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7M5 7l4 4 10-10"/></svg>
                                @elseif($message->status === 'delivered')
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7M5 7l4 4 10-10"/></svg>
                                @else
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-10" x-data>
        @if (session()->has('error'))
            <div class="mb-3 p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 rounded-2xl flex items-center gap-3">
                <div class="p-1 bg-rose-500 rounded-full text-white">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <p class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">{{ session('error') }}</p>
            </div>
        @endif

        <form wire:submit.prevent="sendMessage" class="flex items-center gap-3">
            <button type="button" class="p-3 text-slate-400 hover:text-wa-teal hover:bg-slate-100 dark:hover:bg-slate-800 rounded-2xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </button>

            <div class="flex-1 relative group">
                <textarea wire:model="messageBody" wire:keydown.enter.prevent="sendMessage"
                    placeholder="Type your message..." rows="1"
                    class="w-full py-4 px-6 bg-slate-100 dark:bg-slate-800 border-none focus:ring-2 focus:ring-wa-teal/20 rounded-3xl text-sm font-medium placeholder-slate-400 dark:placeholder-slate-600 resize-none max-h-40 transition-all group-hover:bg-slate-200/50 dark:group-hover:bg-slate-700/50"
                    style="min-height: 56px;"></textarea>
            </div>

            <button type="submit"
                class="h-14 w-14 flex items-center justify-center bg-wa-teal text-white rounded-3xl hover:bg-wa-dark transition-all shadow-lg hover:shadow-wa-teal/20 disabled:opacity-50 group active:scale-95"
                wire:loading.attr="disabled">
                <svg class="w-6 h-6 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
</div>