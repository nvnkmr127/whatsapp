<div x-data="{ 
        status: @entangle('status').live,
        isLocked: @entangle('isLocked').live,
        occupiedBy: @entangle('occupiedBy').live,
        direction: @entangle('direction').live,
        offerSdp: @entangle('offerSdp').live,
        startTime: @entangle('startTime').live,
        
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
        showDebug: false,
        debugLogs: [],
        logIndex: 0,
        isSyncing: false,
        ignoreUpdates: false,

        init() {
            // Initial visibility
            this.updateVisibility(this.status);
            
            // Watchers
            this.$watch('status', val => {
                if (this.ignoreUpdates) return;
                this.updateVisibility(val);
                this.handleStatusChange(val);
            });

            this.$watch('offerSdp', val => this.handleOfferSdp(val));

            // Setup
            this.setupListeners();
            this.handleStatusChange(this.status); // Initial trigger
        },

        updateVisibility(val) {
            const showStatuses = ['ringing', 'active', 'ended', 'in_progress', 'initiated'];
            this.isVisible = showStatuses.includes(val);
        },

        handleStatusChange(val) {
            this.logDebug(`Status: ${val}`);
            
            if (val === 'ringing' && this.direction === 'inbound') this.playRinging();
            if (val === 'active') { this.startTimer(); this.stopRinging(); }
            if (['ended', 'idle', 'failed'].includes(val)) { this.stopTimer(); this.stopRinging(); }

            // Sync other tabs
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

            // Wait for Echo
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
                
                await $wire.initiateWhatsAppCall(data.phone_number, data.contact_id, this.sanitizeSdp(offer.sdp));
                
                this.pc.ontrack = (event) => {
                    const remoteAudio = document.getElementById('remote-audio');
                    if (remoteAudio) remoteAudio.srcObject = event.streams[0];
                };
            } catch (e) {
                this.logDebug(`Outbound error: ${e.message}`, 'error');
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

                // Wait for ICE gathering
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
                this.logDebug(`Answer error: ${e.message}`, 'error');
                this.stopCalling();
            }
        },

        async applyRemoteAnswer(sdp) {
            if (this.pc && sdp && this.pc.signalingState === 'have-local-offer') {
                try {
                    await this.pc.setRemoteDescription({ type: 'answer', sdp: this.sanitizeSdp(sdp) });
                } catch (e) { this.logDebug(`Remote answer error: ${e.message}`, 'error'); }
            }
        },

        handleOfferSdp(value) {
            if (value && this.status === 'ringing' && this.direction === 'outbound') {
                 if (this.pc) this.stopCalling();
                 this.performAction('answerCall');
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
        
        formatDuration(s) { return `${Math.floor(s/60)}:${(s%60).toString().padStart(2, '0')}`; },

        playRinging() { if (this.ringingSound) this.ringingSound.play().catch(() => {}); },
        stopRinging() { if (this.ringingSound) { this.ringingSound.pause(); this.ringingSound.currentTime = 0; } },

        logDebug(msg, type = 'info') {
            const time = new Date().toLocaleTimeString('en-GB', { hour12: false });
            this.debugLogs = [{ id: ++this.logIndex, time, msg, type }, ...this.debugLogs].slice(0, 50);
        }
    }" 
    @auto-hide-overlay.window="setTimeout(() => resetLocal(), 4000)" 
    @call-stopped.window="stopCalling()"
    class="fixed top-8 left-1/2 -translate-x-1/2 z-[9999] pointer-events-none w-full max-w-fit px-6">

    <!-- Premium Call Pill (Dynamic Island Style) -->
    <div x-show="isVisible" 
        x-transition:enter="transition cubic-bezier(0.34, 1.56, 0.64, 1) duration-500"
        x-transition:enter-start="opacity-0 -translate-y-full scale-50 blur-xl"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100 blur-0"
        x-transition:leave="transition cubic-bezier(0.4, 0, 0.2, 1) duration-300"
        x-transition:leave-start="opacity-100 scale-100 blur-0"
        x-transition:leave-end="opacity-0 scale-90 blur-xl translate-y-[-20%]"
        x-cloak
        class="pointer-events-auto group relative min-w-[280px] overflow-hidden rounded-[2.5rem] bg-slate-950/80 backdrop-blur-2xl border border-white/10 shadow-[0_20px_50px_rgba(0,0,0,0.5)] transition-all duration-500 hover:shadow-wa-teal/20"
        :class="{ 'ring-2 ring-wa-teal/50': status === 'active', 'ring-2 ring-rose-500/50': status === 'ended' }">
        
        <!-- Background Glow -->
        <div class="absolute inset-0 opacity-20 transition-colors duration-1000"
            :class="{ 'bg-wa-teal': status === 'active', 'bg-rose-500': status === 'ended', 'bg-blue-500': status === 'ringing' }">
        </div>

        <div class="relative flex items-center gap-4 px-3 py-2.5">
            <!-- Left: Avatar with Status Indicator -->
            <div class="relative flex-shrink-0">
                <div class="absolute -inset-1 rounded-full opacity-50"
                    :class="{ 'animate-ping bg-wa-teal': status === 'ringing' || status === 'active' }"></div>
                <img src="{{ $contactAvatar }}" class="relative w-12 h-12 rounded-full border-2 border-white/10 shadow-inner object-cover" alt="">
                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center border-2 border-slate-950 shadow-lg"
                    :class="{ 'bg-wa-teal text-white': status === 'active', 'bg-rose-500 text-white': status === 'ended', 'bg-blue-500 text-white': status === 'ringing' }">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                </div>
            </div>

            <!-- Middle: Info -->
            <div class="flex flex-col min-w-[120px] max-w-[200px]">
                <span class="text-sm font-black text-white truncate tracking-tight uppercase">{{ $contactName }}</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest"
                        x-text="occupiedBy ? `Agent: ${occupiedBy}` : (status === 'ringing' ? 'Incoming...' : (status === 'active' ? 'Live Call' : 'Finished'))"></span>
                    <template x-if="status === 'active'">
                        <span class="flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-wa-teal animate-pulse"></span>
                            <span class="text-[10px] font-mono text-wa-teal font-black" x-text="formatDuration(duration)">0:00</span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Right: Dynamic Actions -->
            <div class="flex items-center gap-2 pr-2">
                <!-- Answer/Reject Pair -->
                <template x-if="status === 'ringing' && direction === 'inbound'">
                    <div class="flex items-center gap-2">
                        <button @click="performAction('rejectCall')" class="w-10 h-10 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 hover:scale-110 active:scale-90 transition-all shadow-lg">
                            <svg class="w-5 h-5 rotate-[135deg]" fill="currentColor" viewBox="0 0 24 24"><path d="M21 15.46l-5.27-.61-2.52 2.52c-2.83-1.44-5.15-3.75-6.59-6.59l2.53-2.53L8.54 3H3.01C2.45 13.18 10.82 21.55 21 20.99v-5.53z"/></svg>
                        </button>
                        <button @click="performAction('answerCall')" class="w-10 h-10 rounded-full bg-wa-teal text-white flex items-center justify-center hover:bg-wa-teal-dark hover:scale-110 active:scale-95 transition-all shadow-lg animate-bounce">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-2.2 2.2c-2.83-1.44-5.15-3.75-6.59-6.59l2.2-2.21c.28-.26.36-.65.25-1.01A11.332 11.332 0 018.58 4c0-.55-.45-1-1-1H4.11c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.62c0-.55-.45-1-1-1z"/></svg>
                        </button>
                    </div>
                </template>

                <!-- End Call / Cleanup -->
                <template x-if="(status === 'active' || direction === 'outbound' || status === 'ended') && status !== 'idle'">
                    <div class="flex items-center gap-2">
                         <!-- Signal Quality (Active only) -->
                        <template x-if="status === 'active'">
                            <div class="flex items-end gap-0.5 h-3 mr-2">
                                <template x-for="i in 4">
                                    <div class="w-1 rounded-full transition-all duration-500" :class="signalQuality >= (i*25) ? 'bg-wa-teal' : 'bg-white/10'" :style="'height: ' + (i*25) + '%'"></div>
                                </template>
                            </div>
                        </template>

                        <button @click="status === 'ended' ? resetLocal() : performAction('endCall')" 
                            class="w-10 h-10 rounded-full flex items-center justify-center transition-all shadow-lg active:scale-90"
                            :class="status === 'ended' ? 'bg-white/10 text-white hover:bg-white/20' : 'bg-rose-500 text-white hover:bg-rose-600'">
                            <svg x-show="!isProcessing" class="w-5 h-5" :class="status === 'active' ? 'rotate-[135deg]' : ''" fill="currentColor" viewBox="0 0 24 24">
                                <path x-show="status !== 'ended'" d="M21 15.46l-5.27-.61-2.52 2.52c-2.83-1.44-5.15-3.75-6.59-6.59l2.53-2.53L8.54 3H3.01C2.45 13.18 10.82 21.55 21 20.99v-5.53z"/>
                                <path x-show="status === 'ended'" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                            <svg x-show="isProcessing" class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Debug Toggle (Hidden in pill, revealed on hover) -->
        <button @click="showDebug = !showDebug" class="absolute top-1 right-12 w-4 h-4 rounded-full bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
             <span class="text-[8px] text-white/30">D</span>
        </button>

        <!-- Debug Console -->
        <div x-show="showDebug" x-collapse
            class="bg-black/90 p-4 border-t border-white/10 max-h-48 overflow-y-auto font-mono text-[10px] text-indigo-300">
            <div class="flex justify-between mb-2">
                <span class="font-bold text-white/50 tracking-wider">NETWORK CONSOLE</span>
                <button @click="debugLogs = []" class="text-white/30 hover:text-white">CLEAR</button>
            </div>
            <template x-for="log in debugLogs" :key="log.id">
                <div class="mb-1">
                    <span class="text-white/20" x-text="log.time"></span>
                    <span :class="{ 'text-wa-teal': log.type === 'success', 'text-rose-400': log.type === 'error', 'text-amber-300': log.type === 'warn' }" x-text="log.msg"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- Hidden Assets -->
    <audio id="call-ringing-sound" loop preload="auto"><source src="https://assets.mixkit.co/active_storage/sfx/1359/1359-preview.mp3" type="audio/mpeg"></audio>
    <audio id="remote-audio" autoplay></audio>

</div>