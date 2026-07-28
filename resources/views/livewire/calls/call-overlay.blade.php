<div x-data="{ 
        status: $wire.entangle('status').live,
        isLocked: $wire.entangle('isLocked'),
        occupiedBy: $wire.entangle('occupiedBy'),
        direction: $wire.entangle('direction'),
        offerSdp: $wire.entangle('offerSdp'),
        startTime: $wire.entangle('startTime'),
        
        isVisible: false,
        duration: 0,
        timer: null,
        isProcessing: false,
        bc: null,
        pc: null,
        remoteStream: null,
        ringingSound: null,
        signalQuality: 100,
        connectionType: null,
        isSyncing: false,
        ignoreUpdates: false,
        isMuted: false,

        init() {
            this.updateVisibility(this.status);
            
            this.$watch('status', val => {
                if (this.ignoreUpdates) return;
                this.updateVisibility(val);
                this.handleStatusChange(val);
            });

            this.$watch('offerSdp', val => this.handleOfferSdp(val));

            this.setupListeners();
            this.handleStatusChange(this.status);
        },

        updateVisibility(val) {
            const showStatuses = ['ringing', 'active', 'ended', 'in_progress', 'initiated'];
            this.isVisible = showStatuses.includes(val);
        },

        handleStatusChange(val) {
            if (val === 'ringing' && this.direction === 'inbound') this.playRinging();
            if (val === 'active') { this.startTimer(); this.stopRinging(); }
            if (['ended', 'idle', 'failed'].includes(val)) { this.stopTimer(); this.stopRinging(); }

            if (!this.isProcessing && !this.isSyncing && val !== 'idle') {
                this.broadcastSync(val);
            }
        },

        broadcastSync(status) {
            if (!this.bc) return;
            this.bc.postMessage({
                type: 'SYNC_STATE',
                payload: {
                    status: status,
                    startTime: this.startTime,
                    contactName: @js($contactName),
                    contactAvatar: @js($contactAvatar),
                    offerSdp: this.offerSdp
                }
            });
        },

        async setupListeners() {
            this.ringingSound = document.getElementById('call-ringing-sound');
            this.bc = new BroadcastChannel('whatsapp_calls_sync');

            // Wait for Echo to be available
            let attempts = 0;
            while (!window.Echo && attempts < 50) {
                await new Promise(r => setTimeout(r, 100));
                attempts++;
            }

            this.bc.onmessage = (event) => {
                if (event.data.type === 'SYNC_STATE') {
                    this.isSyncing = true;
                    $wire.syncCallState(event.data.payload)
                        .then(() => setTimeout(() => { this.isSyncing = false; }, 500));
                }
            };

            const teamId = @js($this->teamId);
            if (window.Echo && teamId) {
                window.Echo.private(`teams.${teamId}`)
                    .listen('.call.answered', (e) => {
                        this.status = 'active';
                        $wire.startTime = e.call?.answered_at ? Math.floor(new Date(e.call.answered_at).getTime() / 1000) : Math.floor(Date.now() / 1000);
                    })
                    .listen('.call.ended', (e) => { this.status = 'ended'; })
                    .listen('.call.failed', (e) => { this.status = 'ended'; })
                    .listen('.call.ringing', (e) => { if (this.status !== 'active') this.status = 'ringing'; });
            }

            window.addEventListener('trigger-sdp-offer', e => this.handleOutboundCall(e.detail));
            window.addEventListener('set-remote-answer', e => this.applyRemoteAnswer(e.detail.sdp));
        },

        async handleOutboundCall(data) {
            if (this.isProcessing) return;
            this.isProcessing = true;
            this.status = 'ringing'; 
            this.direction = 'outbound';

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
                
                stream.getTracks().forEach(track => this.pc.addTrack(track, stream));
                
                const offer = await this.pc.createOffer();
                await this.pc.setLocalDescription(offer);

                // Wait for ICE gathering to complete so the SDP includes all candidates.
                // Without this the offer would have no ICE candidates and the WebRTC
                // connection could never be established.
                await new Promise(resolve => {
                    if (this.pc.iceGatheringState === 'complete') resolve();
                    else {
                        const check = () => {
                            if (this.pc.iceGatheringState === 'complete') {
                                this.pc.removeEventListener('icegatheringstatechange', check);
                                resolve();
                            }
                        };
                        this.pc.addEventListener('icegatheringstatechange', check);
                        setTimeout(resolve, 3000); // 3 s safety timeout
                    }
                });

                await $wire.initiateWhatsAppCall(data.phone_number, data.contact_id, this.sanitizeSdp(this.pc.localDescription.sdp));
                
                this.pc.ontrack = (event) => {
                    const remoteAudio = document.getElementById('remote-audio');
                    if (remoteAudio) remoteAudio.srcObject = event.streams[0];
                };
            } catch (e) {
                this.stopCalling();
            } finally {
                this.isProcessing = false;
            }
        },

        async handleAnswer() {
            try {
                this.pc = new RTCPeerConnection({
                    iceServers: [{ urls: 'stun:stun.l.google.com:19302' }, { urls: 'stun:stun1.l.google.com:19302' }],
                    iceCandidatePoolSize: 10
                });

                this.pc.ontrack = (event) => {
                    const remoteAudio = document.getElementById('remote-audio');
                    if (remoteAudio) {
                        remoteAudio.srcObject = event.streams[0];
                        remoteAudio.play().catch(e => {});
                    }
                };

                const localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                localStream.getTracks().forEach(track => this.pc.addTrack(track, localStream));

                await this.pc.setRemoteDescription({ type: 'offer', sdp: this.sanitizeSdp(this.offerSdp) });
                const answer = await this.pc.createAnswer();
                await this.pc.setLocalDescription(answer);

                await new Promise(resolve => {
                    if (this.pc.iceGatheringState === 'complete') resolve();
                    else {
                        const check = () => { if (this.pc.iceGatheringState === 'complete') { this.pc.removeEventListener('icegatheringstatechange', check); resolve(); }};
                        this.pc.addEventListener('icegatheringstatechange', check);
                        setTimeout(resolve, 2000);
                    }
                });

                await $wire.answerCall(this.sanitizeSdp(this.pc.localDescription.sdp));
            } catch (e) {
                this.stopCalling();
            }
        },

        async applyRemoteAnswer(sdp) {
            if (this.pc && sdp && this.pc.signalingState === 'have-local-offer') {
                try {
                    await this.pc.setRemoteDescription({ type: 'answer', sdp: this.sanitizeSdp(sdp) });
                } catch (e) { }
            }
        },

        handleOfferSdp(value) {
            // For outbound calls: offerSdp receives the remote party's SDP *answer* via
            // webhook → CallOverlay::handleAnswered().  We must apply it as a remote
            // answer on our existing RTCPeerConnection — NOT start a brand-new
            // inbound-style handshake with handleAnswer().
            if (value && this.status === 'ringing' && this.direction === 'outbound') {
                this.applyRemoteAnswer(value);
            }
        },

        sanitizeSdp(sdp) {
            return sdp.replace(/[^\x20-\x7E\r\n]/g, '').replace(/\r\n|\r|\n/g, '\n').split('\n').map(l => l.trim()).filter(l => l).join('\r\n') + '\r\n';
        },

        performAction(action) {
            if (this.isProcessing || this.isLocked) return;
            this.isProcessing = true;
            (action === 'answerCall' ? this.handleAnswer() : $wire[action]())
                .finally(() => { this.isProcessing = false; });
        },

        resetLocal() {
            this.ignoreUpdates = true;
            this.status = 'idle';
            this.isVisible = false;
            this.stopCalling();
            $wire.resetOverlay();
            setTimeout(() => { this.ignoreUpdates = false; }, 2000);
        },

        stopCalling() {
            if (this.pc) { this.pc.close(); this.pc = null; }
            const remoteAudio = document.getElementById('remote-audio');
            if (remoteAudio) remoteAudio.srcObject = null;
        },

        startTimer() {
            this.stopTimer();
            this.duration = this.startTime ? Math.max(0, Math.floor(Date.now() / 1000) - this.startTime) : 0;
            this.timer = setInterval(() => { this.duration++; }, 1000);
        },

        stopTimer() { if (this.timer) clearInterval(this.timer); this.timer = null; },
        
        toggleMute() {
            if (!this.pc) return;
            this.isMuted = !this.isMuted;
            this.pc.getSenders().forEach(sender => {
                if (sender.track.kind === 'audio') {
                    sender.track.enabled = !this.isMuted;
                }
            });
        },

        formatDuration(s) { 
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const secs = s % 60;
            return (h > 0 ? h + ':' : '') + m.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
        },

        playRinging() { if (this.ringingSound) this.ringingSound.play().catch(() => {}); },
        stopRinging() { if (this.ringingSound) { this.ringingSound.pause(); this.ringingSound.currentTime = 0; } }

    }" @auto-hide-overlay.window="setTimeout(() => resetLocal(), 4000)" @call-stopped.window="stopCalling()"
    class="fixed top-6 left-1/2 -translate-x-1/2 z-[9999] pointer-events-none w-full max-w-fit px-6 font-sans">

    <style>
        @keyframes voice-wave {

            0%,
            100% {
                height: 4px;
                opacity: 0.5;
            }

            50% {
                height: 16px;
                opacity: 1;
            }
        }

        .voice-bar {
            width: 3px;
            background-color: var(--wa-brand);
            border-radius: 4px;
            animation: voice-wave 1s ease-in-out infinite;
        }

        .voice-bar:nth-child(2) {
            animation-delay: 0.1s;
        }

        .voice-bar:nth-child(3) {
            animation-delay: 0.2s;
        }

        .voice-bar:nth-child(4) {
            animation-delay: 0.3s;
        }

        .voice-bar:nth-child(5) {
            animation-delay: 0.4s;
        }

        .glass-island {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow:
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 20px 40px -12px rgba(0, 0, 0, 0.5);
        }

        .premium-glow-active {
            box-shadow: 0 0 40px -10px rgba(37, 211, 102, 0.3);
            border-color: rgba(37, 211, 102, 0.4);
        }

        .premium-glow-ringing {
            box-shadow: 0 0 40px -10px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.4);
        }

        .premium-glow-ended {
            box-shadow: 0 0 40px -10px rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.4);
        }
    </style>

    <!-- Premium Call Island -->
    <div x-show="isVisible" x-transition:enter="transition cubic-bezier(0.175, 0.885, 0.32, 1.275) duration-600"
        x-transition:enter-start="opacity-0 -translate-y-24 scale-75 blur-2xl"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100 blur-0"
        x-transition:leave="transition cubic-bezier(0.4, 0, 1, 1) duration-300"
        x-transition:leave-start="opacity-100 scale-100 blur-0"
        x-transition:leave-end="opacity-0 scale-90 blur-xl -translate-y-12" x-cloak
        class="pointer-events-auto group glass-island relative min-w-[320px] rounded-[2.5rem] p-2 transition-all duration-500"
        :class="{ 
            'premium-glow-active': status === 'active', 
            'premium-glow-ringing': status === 'ringing',
            'premium-glow-ended': status === 'ended' 
        }">

        <!-- Internal Content Layout -->
        <div class="flex items-center justify-between gap-4">

            <!-- Left: Avatar & Source Icon -->
            <div class="relative flex-none pl-1">
                <div class="relative">
                    <!-- Ringing Pulse -->
                    <template x-if="status === 'ringing'">
                        <div class="absolute inset-0 rounded-full bg-blue-500/30 animate-ping"></div>
                    </template>
                    <template x-if="status === 'active'">
                        <div class="absolute inset-0 rounded-full bg-wa-teal/20 animate-pulse"></div>
                    </template>

                    <img src="{{ $contactAvatar }}"
                        class="relative w-12 h-12 rounded-full border-2 border-white/10 object-cover shadow-2xl" alt="">

                    <!-- WhatsApp Brand Badge -->
                    <div
                        class="absolute -bottom-1 -right-1 w-5 h-5 bg-wa-brand rounded-full flex items-center justify-center border-2 border-slate-900 shadow-md">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.181-2.587-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793 0-.852.449-1.271.609-1.445.16-.173.348-.217.464-.217l.333.006c.077.005.156-.005.247.217.115.285.405 1.012.441 1.085.035.073.059.158.01.257-.043.1-.064.162-.127.24-.064.077-.133.172-.191.23-.064.063-.131.131-.057.257.073.127.327.539.702.874.482.43 0.887.562 1.012.614.125.052.199.043.272-.04.073-.082.316-.367.4-.493.085-.125.17-.105.287-.061.117.043.74.349.869.412.13.064.217.094.249.145.033.05.033.291-.112.696z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Center: Calling Info -->
            <div class="flex-grow min-w-0">
                <div class="flex flex-col">
                    <h3 class="text-[15px] font-bold text-white truncate leading-tight tracking-tight">
                        {{ $contactName }}
                    </h3>

                    <div class="flex items-center gap-2 mt-0.5">
                        <!-- Status Label -->
                        <span class="text-[10px] font-black uppercase tracking-[0.1em] transition-colors duration-300"
                            :class="{ 
                                'text-blue-400': status === 'ringing',
                                'text-wa-teal': status === 'active',
                                'text-rose-400': status === 'ended'
                             }"
                            x-text="occupiedBy ? `Agent: ${occupiedBy}` : (status === 'ringing' ? 'Incoming...' : (status === 'active' ? 'Live Call' : 'Call Ended'))">
                        </span>

                        <!-- Pulse Dot (Active) -->
                        <span x-show="status === 'active'" class="flex items-center gap-1.5 h-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-wa-teal animate-pulse"></span>
                            <span class="text-xs font-mono font-bold text-white/90"
                                x-text="formatDuration(duration)">00:00</span>
                        </span>

                        <!-- Voice Wave (Active) -->
                        <div x-show="status === 'active'" class="flex items-end gap-0.5 h-3 ml-1">
                            <div class="voice-bar"></div>
                            <div class="voice-bar"></div>
                            <div class="voice-bar"></div>
                            <div class="voice-bar"></div>
                            <div class="voice-bar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Dynamic Actions -->
            <div class="flex items-center gap-2 pr-2">

                <!-- Answer/Reject Container (Ringing) -->
                <template x-if="status === 'ringing' && direction === 'inbound'">
                    <div class="flex items-center gap-3">
                        <button @click="performAction('rejectCall')"
                            class="w-11 h-11 rounded-full bg-rose-500/90 text-white flex items-center justify-center hover:bg-rose-500 hover:scale-110 active:scale-95 transition-all shadow-lg backdrop-blur-sm group/btn">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4 11H8v-2h8v2z" />
                            </svg>
                        </button>

                        <button @click="performAction('answerCall')"
                            class="w-11 h-11 rounded-full bg-wa-teal text-white flex items-center justify-center hover:bg-wa-teal-dark hover:scale-110 active:scale-95 transition-all shadow-lg animate-bounce group/btn">
                            <svg class="w-6 h-6 group-hover/btn:scale-110 transition-transform" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-2.2 2.2c-2.83-1.44-5.15-3.75-6.59-6.59l2.2-2.21c.28-.26.36-.65.25-1.01A11.332 11.332 0 018.58 4c0-.55-.45-1-1-1H4.11c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.62c0-.55-.45-1-1-1z" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Active/End Container -->
                <template
                    x-if="(['active', 'ended', 'initiated', 'ringing'].includes(status) && !(status === 'ringing' && direction === 'inbound'))">
                    <div class="flex items-center gap-3">

                        <!-- Mute & Signal (Active only) -->
                        <template x-if="status === 'active'">
                            <div
                                class="flex items-center gap-2 bg-white/5 rounded-2xl border border-white/5 pl-2 pr-1 py-1">
                                <button @click="toggleMute()"
                                    class="w-8 h-8 rounded-full flex items-center justify-center transition-all"
                                    :class="isMuted ? 'bg-rose-500/80 text-white' : 'text-white/60 hover:text-white hover:bg-white/5'">
                                    <svg x-show="!isMuted" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z" />
                                        <path
                                            d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
                                    </svg>
                                    <svg x-show="isMuted" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M19 11h-1.7c0 .74-.16 1.43-.43 2.05l1.23 1.23c.56-.98.9-2.09.9-3.28zm-4.02.17c0-.06.02-.11.02-.17V5c0-1.66-1.34-3-3-3S9 3.34 9 5v.18l5.98 5.99zM4.27 3L3 4.27l6.01 6.01V11c0 1.66 1.33 3 2.99 3 .22 0 .44-.03.65-.08l1.66 1.66c-.71.33-1.5.52-2.31.52-2.76 0-5.3-2.1-5.3-5.1H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c.91-.13 1.77-.45 2.54-.9L19.73 21 21 19.73 4.27 3z" />
                                    </svg>
                                </button>
                                <div class="flex items-end gap-1 px-1.5 h-6">
                                    <template x-for="i in [0.4, 0.6, 0.8, 1]">
                                        <div class="w-0.5 rounded-full transition-all duration-700"
                                            :class="signalQuality >= (i*100) ? 'bg-wa-teal' : 'bg-white/10'"
                                            :style="'height: ' + (i*10) + 'px'"></div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Action Button (End/Close) -->
                        <button @click="status === 'ended' ? resetLocal() : performAction('endCall')"
                            class="w-11 h-11 rounded-full flex items-center justify-center transition-all duration-300 shadow-lg active:scale-90 group/end relative overflow-hidden"
                            :class="status === 'ended' ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-rose-500 text-white hover:bg-rose-600'">

                            <template x-if="!isProcessing">
                                <div>
                                    <!-- End Icon -->
                                    <svg x-show="status !== 'ended'" class="w-6 h-6 transition-transform duration-300"
                                        :class="status === 'active' || direction === 'outbound' ? 'rotate-[135deg]' : ''"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M21 15.46l-5.27-.61-2.52 2.52c-2.83-1.44-5.15-3.75-6.59-6.59l2.53-2.53L8.54 3H3.01C2.45 13.18 10.82 21.55 21 20.99v-5.53z" />
                                    </svg>
                                    <!-- Close Icon -->
                                    <svg x-show="status === 'ended'" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                            </template>

                            <!-- Loading Spinner -->
                            <div x-show="isProcessing"
                                class="absolute inset-0 flex items-center justify-center bg-inherit">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Bottom Tray (Optional expansion for metadata) -->
        <div x-show="status === 'active'" x-collapse class="px-3 pb-2 pt-0.5">
            <div class="h-[1px] w-full bg-white/5 mb-2"></div>
            <div class="flex items-center justify-between px-2">
                <span class="text-[9px] text-white/40 uppercase tracking-widest font-bold">Secure WhatsApp
                    Connection</span>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-wa-teal"></span>
                    <span class="text-[9px] text-wa-teal font-black">ENCRYPTED</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Hidden Audio Assets -->
    <audio id="call-ringing-sound" loop preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/1359/1359-preview.mp3" type="audio/mpeg">
    </audio>
    <audio id="remote-audio" autoplay></audio>

</div>