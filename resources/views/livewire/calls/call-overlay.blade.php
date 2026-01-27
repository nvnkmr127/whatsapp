<div x-data="{ 
        status: @entangle('status'),
        isLocked: @entangle('isLocked'),
        occupiedBy: @entangle('occupiedBy'),
        duration: 0,
        timer: null,
        isProcessing: false,
        bc: null,
        direction: @entangle('direction'),
        
        init() {
            this.bc = new BroadcastChannel('whatsapp_calls_sync');
            this.bc.onmessage = (event) => {
                if (event.data.type === 'SYNC_STATE') {
                    $wire.syncCallState(event.data.payload);
                }
            };

            $watch('status', value => {
                if (value === 'active') this.startTimer();
                if (value === 'ended' || value === 'idle') this.stopTimer();
                
                // Sync other tabs
                if (!this.isProcessing) {
                    this.bc.postMessage({
                        type: 'SYNC_STATE',
                        payload: {
                            status: value,
                            startTime: $wire.startTime,
                            contactName: $wire.contactName,
                            contactAvatar: $wire.contactAvatar
                        }
                    });
                }
            });
        },
        
        startTimer() {
            if (this.timer) clearInterval(this.timer);
            this.duration = 0;
            this.timer = setInterval(() => {
                this.duration++;
            }, 1000);
        },
        
        stopTimer() {
            if (this.timer) clearInterval(this.timer);
            this.timer = null;
        },
        
        formatDuration(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },

        async performAction(action) {
            if (this.isProcessing || this.isLocked) return;
            this.isProcessing = true;
            
            try {
                await $wire[action]();
            } finally {
                this.isProcessing = false;
            }
        }
    }" @auto-hide-overlay.window="setTimeout(() => $wire.resetOverlay(), 3000)"
    class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4 pointer-events-none">
    <!-- Overlay Container -->
    <div x-show="status !== 'idle'" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 -translate-y-10 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-10 scale-90" x-cloak
        class="pointer-events-auto relative overflow-hidden rounded-[2rem] border border-white/20 shadow-2xl backdrop-blur-2xl transition-all duration-500"
        :class="{
            'bg-slate-900/90': (status === 'ringing' || occupiedBy),
            'bg-wa-teal/90': (status === 'active' && !occupiedBy),
            'bg-rose-600/90': status === 'ended'
        }">
        <!-- Background Decorative Elements -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-black/10 rounded-full blur-2xl"></div>

        <div class="relative px-6 py-5 flex items-center justify-between gap-4">
            <!-- Left: Avatar & Info -->
            <div class="flex items-center gap-4">
                <div class="relative">
                    <!-- Pulsing ring for ringing state -->
                    <template x-if="status === 'ringing'">
                        <div class="absolute -inset-1.5 bg-white/20 rounded-full animate-ping"></div>
                    </template>

                    <img src="{{ $contactAvatar }}"
                        class="relative w-12 h-12 rounded-2xl border-2 border-white/30 shadow-lg" alt="">

                    <!-- Call Indicator Icon -->
                    <div class="absolute -bottom-1 -right-1 p-1 rounded-lg shadow-md"
                        :class="status === 'active' ? 'bg-white text-wa-teal' : 'bg-wa-teal text-white'">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                    </div>
                </div>

                <div class="flex flex-col">
                    <h3 class="text-sm font-black text-white tracking-tight uppercase"
                        :class="occupiedBy ? 'text-rose-400' : ''">{{ $contactName }}</h3>
                    <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mt-0.5" x-text="
                        occupiedBy ? `Taken by ${occupiedBy}` :
                        (status === 'ringing' ? 'Initiating Call...' : 
                        (status === 'active' ? 'In Call' : 'Call Ended'))
                    "></p>
                </div>
            </div>

            <!-- Middle/Right: Duration (Only if active) -->
            <div x-show="status === 'active'" class="flex flex-col items-center">
                <span class="text-lg font-mono font-black text-white tracking-tighter"
                    x-text="formatDuration(duration)">0:00</span>
                <span class="text-[8px] font-black text-white/50 uppercase tracking-[0.2em]">Duration</span>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2">
                <template x-if="status === 'ringing' && direction === 'inbound'">
                    <div class="flex items-center gap-2">
                        <!-- Reject Button -->
                        <button @click="performAction('rejectCall')"
                            class="p-3 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white transition-all duration-300 transform hover:scale-110 active:scale-90 shadow-lg"
                            :disabled="isProcessing">
                            <svg class="w-5 h-5 rotate-[135deg]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M21 15.46l-5.27-.61-2.52 2.52c-2.83-1.44-5.15-3.75-6.59-6.59l2.53-2.53L8.54 3H3.01C2.45 13.18 10.82 21.55 21 20.99v-5.53z" />
                            </svg>
                        </button>

                        <!-- Answer Button -->
                        <button @click="performAction('answerCall')"
                            class="p-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white transition-all duration-300 transform hover:scale-110 active:scale-90 shadow-lg animate-bounce"
                            :disabled="isProcessing">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-2.2 2.2c-2.83-1.44-5.15-3.75-6.59-6.59l2.2-2.21c.28-.26.36-.65.25-1.01A11.332 11.332 0 018.58 4c0-.55-.45-1-1-1H4.11c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.62c0-.55-.45-1-1-1z" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- End Call Button (Show for active calls or outbound ringing) -->
                <template x-if="status === 'active' || (status === 'ringing' && direction === 'outbound') || status === 'ended'">
                    <button @click="performAction('endCall')"
                        class="group p-3 rounded-2xl transition-all duration-300 transform hover:scale-110 active:scale-90 shadow-lg"
                        :class="(status === 'ended' || isProcessing || isLocked) ? 'bg-white/10 text-white/20 cursor-not-allowed' : 'bg-rose-500 hover:bg-rose-600 text-white'">
                        <svg x-show="!isProcessing" class="w-5 h-5 rotate-[135deg]" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M21 15.46l-5.27-.61-2.52 2.52c-2.83-1.44-5.15-3.75-6.59-6.59l2.53-2.53L8.54 3H3.01C2.45 13.18 10.82 21.55 21 20.99v-5.53z" />
                        </svg>
                        <svg x-show="isProcessing" class="animate-spin h-5 w-5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        <!-- Progress/Status Line at Bottom -->
        <div class="h-1 w-full bg-black/10 overflow-hidden">
            <div class="h-full bg-white transition-all duration-1000 ease-linear" :class="{ 
                    'animate-progress-infinite': status === 'ringing',
                    'w-full': status === 'ended',
                    'opacity-0': status === 'active'
                }"></div>
        </div>
    </div>

    <style>
        @keyframes progress-infinite {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .animate-progress-infinite {
            animation: progress-infinite 2s infinite;
            width: 30%;
        }

        .animate-pulse-slow {
            animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }
    </style>
</div>