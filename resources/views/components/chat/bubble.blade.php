<div x-data="{ showMenu: false, showEmoji: false }" :id="'message-' + message.id" :class="['flex items-center gap-2', message.is_outbound ? 'flex-row-reverse justify-start' : 'justify-start', 'mb-6 message-appear relative group/msg transition-colors duration-500 rounded-xl']">

    <div class="max-w-[85%] sm:max-w-[70%] group relative flex items-center gap-2" :class="message.is_outbound ? 'flex-row-reverse' : 'flex-row'">
        
        <!-- Chat Bubble inner -->
        <div :class="[ 
            'relative p-3 px-4 transition-all shadow-sm',
            message.is_outbound 
                ? 'bg-wa-teal text-white rounded-2xl rounded-tr-sm shadow-xl shadow-wa-teal/10' 
                : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-sm border border-slate-100 dark:border-slate-700'
        ]">
            
            <!-- Chevron Button -->
            <button @click="showMenu = !showMenu; if(showMenu) $event.stopPropagation();" 
                    class="absolute top-1 right-1 p-0.5 rounded-full bg-black/10 dark:bg-white/20 text-white opacity-0 group-hover/msg:opacity-100 transition-opacity z-20 backdrop-blur-sm"
                    :class="message.is_outbound ? 'text-white bg-black/10 hover:bg-black/20' : 'text-slate-500 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="showMenu" @click.away="showMenu = false" x-cloak x-transition
                 class="absolute top-6 z-50 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700"
                 :class="message.is_outbound ? 'right-0' : 'left-0'">
                 
                <!-- Menu Items -->
                <div class="py-2 flex flex-col relative z-10 bg-white dark:bg-slate-800 rounded-2xl">
                    <button @click="$dispatch('reply-to-message', message); showMenu = false" class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 w-full text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Reply
                    </button>
                    <button @click="navigator.clipboard.writeText(message.content || ''); window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: 'Copied to clipboard' } })); showMenu = false" class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 w-full text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy
                    </button>
                    <button @click="$wire.openForwardModal(message.id); showMenu = false" class="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 w-full text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        Forward
                    </button>
                </div>
            </div>

            <!-- Attribution Badge -->
            <template x-if="message.attributed_campaign_name">
                <div
                    class="mb-2 flex items-center gap-1.5 px-2 py-1 rounded-lg bg-wa-teal/10 dark:bg-wa-teal/20 border border-wa-teal/20">
                    <svg class="w-3 h-3 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167a2.405 2.405 0 011.002-2.736l3.144-1.921A1.76 1.76 0 0111 5.882zM15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-[9px] font-black text-wa-teal uppercase tracking-widest">
                        Reply to: <span x-text="message.attributed_campaign_name"></span>
                    </span>
                </div>
            </template>

            <!-- Quoted Message -->
            <template x-if="message.reply_to_message">
                <div class="mb-2 w-full">
                    <div class="bg-black/5 dark:bg-white/10 rounded-lg overflow-hidden flex cursor-pointer hover:bg-black/10 dark:hover:bg-white/20 transition-colors"
                         @click="$dispatch('chat-scroll-to-id', { id: message.reply_to_message_id })">
                        <div class="w-1 shrink-0" :class="message.reply_to_message.is_outbound ? 'bg-wa-teal' : 'bg-[#d95a2b]'"></div>
                        <div class="flex-1 p-2">
                            <p class="text-[11px] font-bold mb-0.5 truncate" :class="message.reply_to_message.is_outbound ? 'text-wa-teal' : 'text-[#d95a2b]'" x-text="message.reply_to_message.is_outbound ? 'You' : 'Contact'"></p>
                            <p class="text-[11px] text-slate-700 dark:text-slate-300 truncate opacity-80" x-text="message.reply_to_message.content"></p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Media -->
            <template x-if="message.media_url">
                <div class="mb-3 rounded-lg overflow-hidden border border-white/10">
                    <template x-if="message.media_type && message.media_type.startsWith('image')">
                        <img :src="message.media_url"
                            class="w-full max-h-80 object-cover cursor-pointer hover:opacity-90 rounded-lg shadow-sm"
                            loading="lazy"
                            @click="lightboxImage = message.media_url; lightboxOpen = true">
                    </template>
                    <template x-if="message.media_type && message.media_type.startsWith('video')">
                        <video :src="message.media_url" controls class="w-full max-h-80" preload="metadata"></video>
                    </template>
                    <template x-if="message.media_type && message.media_type.startsWith('audio')">
                        <div x-data="{ 
                                    playing: false, 
                                    duration: 0, 
                                    current: 0,
                                    audio: null,
                                    bars: [],
                                    init() {
                                        let seed = parseInt(String(message.id).replace(/\D/g, '')) || 1;
                                        this.bars = Array.from({length: 25}, (_, i) => 30 + ((seed * (i + 3)) % 70));
                                    },
                                    toggle() {
                                        if (!this.audio) {
                                            this.audio = new Audio(message.media_url);
                                            this.audio.onloadedmetadata = () => { this.duration = this.audio.duration; };
                                            this.audio.ontimeupdate = () => { this.current = this.audio.currentTime; };
                                            this.audio.onended = () => { this.playing = false; this.current = 0; };
                                        }
                                        if (this.playing) { this.audio.pause(); } 
                                        else { this.audio.play(); }
                                        this.playing = !this.playing;
                                    },
                                    formatTime(s) {
                                        if(!s || isNaN(s)) return '0:00';
                                        let min = Math.floor(s / 60);
                                        let sec = Math.floor(s % 60);
                                        return min + ':' + (sec < 10 ? '0' : '') + sec;
                                    }
                                }"
                            class="flex items-center gap-3 p-3 bg-black/5 dark:bg-black/20 rounded-2xl min-w-[200px]">
                            <!-- Play/Pause Button -->
                            <button @click="toggle"
                                class="w-10 h-10 flex items-center justify-center bg-wa-teal text-white rounded-full shadow-md shrink-0 hover:scale-105 active:scale-95 transition-transform">
                                <template x-if="!playing">
                                    <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </template>
                                <template x-if="playing">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1">
                                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                                    </svg>
                                </template>
                            </button>

                            <!-- Waveform Area -->
                            <div class="flex-1 flex flex-col gap-1">
                                <div class="flex items-end gap-[2px] h-6">
                                    <template x-for="(h, i) in bars" :key="i">
                                        <div class="w-[3px] rounded-full transition-colors duration-200"
                                            :class="(current/duration) * bars.length >= i ? 'bg-wa-teal' : 'bg-slate-300 dark:bg-slate-700'"
                                            :style="'height: ' + h + '%'"></div>
                                    </template>
                                </div>
                                <div
                                    class="flex justify-between items-center text-[9px] font-black uppercase tracking-tighter text-slate-500 dark:text-slate-400">
                                    <span x-text="formatTime(current)">0:00</span>
                                    <span x-text="formatTime(duration)"></span>
                                </div>
                            </div>
                        </div>
                        <!-- Transcription -->
                        <template x-if="message.metadata && message.metadata.transcription">
                            <div class="mt-2 p-2 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-800/50">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <x-icon name="sparkles" class="w-2.5 h-2.5 text-wa-teal" />
                                    <span class="text-[8px] font-black uppercase tracking-widest text-wa-teal">AI Transcription</span>
                                </div>
                                <p class="text-[11px] font-medium text-slate-600 dark:text-slate-300 italic leading-relaxed" x-text="message.metadata.transcription"></p>
                            </div>
                        </template>
                    </template>
                    <template
                        x-if="!message.media_type || (!message.media_type.startsWith('image') && !message.media_type.startsWith('video') && !message.media_type.startsWith('audio'))">
                        <a :href="message.media_url" target="_blank"
                            class="flex items-center gap-3 p-3 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="font-bold text-xs truncate">Document</span>
                        </a>
                    </template>
                </div>
            </template>

            <!-- Media placeholder: inbound-only, shown while DownloadMediaJob hasn't
                 populated media_url yet, so a received photo/video is visible instead
                 of an empty bubble. Outbound media always has media_url at send time.
                 ponytail: a permanently-failed download shows this spinner forever;
                 add a 'failed' state when messages gain a media_status column. -->
            <template x-if="!message.is_outbound && !message.media_url && ['image','video','audio','document','sticker'].includes(message.type)">
                <div class="mb-3 flex items-center gap-2 p-3 rounded-lg bg-black/5 dark:bg-black/20 text-slate-500 dark:text-slate-400 text-xs italic">
                    <svg aria-hidden="true" class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="message.type.charAt(0).toUpperCase() + message.type.slice(1) + ' — downloading…'"></span>
                </div>
            </template>

            <!-- Text -->
            <template x-if="message.content && message.content !== '[Image]'">
                <p class="text-xs sm:text-sm font-medium whitespace-pre-wrap leading-relaxed" x-text="message.content">
                </p>
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
                            <svg class="w-3 h-3 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7M5 7l4 4 10-10" />
                            </svg>
                        </template>
                        <template x-if="message.status === 'delivered'">
                            <svg class="w-3 h-3 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7M5 7l4 4 10-10" />
                            </svg>
                        </template>
                        <template x-if="message.status === 'sent'">
                            <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="message.status === 'failed'">
                            <div class="group/error relative flex items-center gap-1">
                                <span
                                    class="text-[8px] font-black text-rose-300 uppercase cursor-pointer hover:underline"
                                    @click="$store.chat.retryMessage(message.id)">Retry</span>
                                <svg class="w-3 h-3 text-rose-300 cursor-help" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </template>
                        <template x-if="['queued', 'sending'].includes(message.status)">
                            <svg class="w-3 h-3 text-white/40 animate-pulse" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                    </span>
                </template>
            </div>
        </div>

        <!-- Active Reactions -->
        <template
            x-if="message.metadata && message.metadata.reactions && Object.keys(message.metadata.reactions).length > 0">
            <div class="absolute -bottom-3 flex flex-wrap gap-1 z-10 right-2">
                <template x-for="(emoji, agentId) in message.metadata.reactions">
                    <div class="bg-white dark:bg-slate-700 shadow-sm border border-slate-200 dark:border-slate-600 rounded-full w-7 h-7 flex items-center justify-center text-sm transform hover:scale-110 transition-transform cursor-pointer"
                        :title="'Agent ' + agentId">
                        <span x-text="emoji"></span>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Action Buttons (Hover) beside the bubble -->
    <div class="opacity-0 group-hover/msg:opacity-100 transition-opacity flex items-center gap-1 z-10">
        <div class="relative">
            <button @click="showEmoji = !showEmoji" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <!-- Floating Emojis -->
            <div x-show="showEmoji" @click.away="showEmoji = false" x-cloak x-transition
                 class="absolute top-8 flex items-center gap-1.5 bg-white dark:bg-slate-800 shadow-lg border border-slate-100 dark:border-slate-700 rounded-full px-3 py-1.5"
                 :class="message.is_outbound ? 'right-0' : 'left-0'">
                <template x-for="emoji in ['👍', '❤️', '😂', '😮', '😢', '🙏']">
                    <button @click="$wire.addReaction(message.id, emoji); showEmoji = false"
                        class="hover:scale-125 transition-transform text-lg leading-none p-1" x-text="emoji"></button>
                </template>
            </div>
        </div>
    </div>
</div>