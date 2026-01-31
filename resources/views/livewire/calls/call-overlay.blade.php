<div x-data="{ 
        status: @entangle('status').live,
        isLocked: @entangle('isLocked').live,
        occupiedBy: @entangle('occupiedBy').live,
        duration: 0,
        timer: null,
        isProcessing: false,
        bc: null,
        direction: @entangle('direction').live,
        offerSdp: @entangle('offerSdp').live,
        pc: null,
        remoteStream: null,
        ringingSound: null,
        signalQuality: 100,
        connectionType: null,
        startTime: @entangle('startTime').live,
        showDebug: false,
        debugLogs: [],
        logIndex: 0,

        isSyncing: false,

        logDebug(msg, type = 'info') {
            const time = new Date().toLocaleTimeString('en-GB', { hour12: false });
            const newLog = { id: ++this.logIndex, time: time, msg: msg, type: type };
            this.debugLogs = [newLog, ...this.debugLogs].slice(0, 50);
            console.log(`[CallDebug] ${msg}`);
        },
        async init() {
            this.logDebug('Overlay initializing...');
            this.ringingSound = document.getElementById('call-ringing-sound');
            this.bc = new BroadcastChannel('whatsapp_calls_sync');

            // Wait for Echo
            let attempts = 0;
            while (!window.Echo && attempts < 50) {
                await new Promise(r => setTimeout(r, 100));
                attempts++;
            }
            
            if (window.Echo) {
                this.logDebug('Echo detected and ready.');
            } else {
                this.logDebug('Echo NOT detected after 5s', 'error');
            }

            this.bc.onmessage = (event) => {
                if (event.data.type === 'SYNC_STATE') {
                    // Prevent feedback loop
                    this.isSyncing = true;
                    this.logDebug('Sync state received via BroadcastChannel');
                    $wire.syncCallState(event.data.payload)
                        .then(() => {
                            setTimeout(() => { this.isSyncing = false; }, 500);
                        });
                }
            };

            window.addEventListener('call-stopped', () => {
                this.logDebug('Call stopped event received');
                this.stopCalling();
            });

            // Echo Listeners for real-time updates (fallback/sync)
            const teamId = @js($this->teamId);
            if (window.Echo && teamId) {
                this.logDebug(`Subscribing to private channel: teams.${teamId}`);
                window.Echo.private(`teams.${teamId}`)
                    .subscribed(() => {
                        this.logDebug(`Successfully joined channel: teams.${teamId}`, 'success');
                    })
                    .error((err) => {
                        this.logDebug(`Echo Subscription Error: ${JSON.stringify(err)}`, 'error');
                    })
                    .listen('.call.answered', (e) => {
                        this.logDebug('SIGNAL: Call Answered received', 'success');
                        this.status = 'active';
                        if (e.call && e.call.answered_at) {
                            const answeredAt = Math.floor(new Date(e.call.answered_at).getTime() / 1000);
                            $wire.startTime = answeredAt;
                        } else {
                            $wire.startTime = Math.floor(Date.now() / 1000);
                        }
                    })
                    .listen('.listen-error', (e) => this.logDebug(`Echo Error: ${JSON.stringify(e)}`, 'error'))
                    .listen('.call.ended', (e) => {
                        this.logDebug('SIGNAL: Call Ended received', 'warn');
                        this.status = 'ended';
                    })
                    .listen('.call.failed', (e) => {
                        this.logDebug('SIGNAL: Call Failed received', 'error');
                        this.status = 'ended';
                    })
                    .listen('.call.ringing', (e) => {
                        this.logDebug('SIGNAL: Phone Ringing');
                        if (this.status !== 'active') this.status = 'ringing';
                    });
            } else {
                this.logDebug('Echo not fully initialized or teamId missing', 'warn');
            }

            $watch('status', value => {
                this.logDebug(`UI Status changed to: ${value}`);
                if (value === 'ringing' && this.direction === 'inbound') this.playRinging();
                if (value === 'active') { this.startTimer(); this.stopRinging(); }
                if (value === 'ended' || value === 'idle' || value === 'failed') { this.stopTimer(); this.stopRinging(); }
                
                // Sync other tabs ensure we don't loop
                if (!this.isProcessing && !this.isSyncing) {
                    this.bc.postMessage({
                        type: 'SYNC_STATE',
                        payload: {
                            status: value,
                            startTime: this.startTime,
                            contactName: $wire.contactName,
                            contactAvatar: $wire.contactAvatar,
                            offerSdp: this.offerSdp
                        }
                    });
                }
            });

            // Auto-answer for outbound calls once we get the SDP offer from Meta (Handling Glare)
            $watch('offerSdp', value => {
                if (value && this.status === 'ringing' && this.direction === 'outbound') {
                    this.logDebug('Detected Meta offer for outbound - re-negotiating...');
                    
                    // If we have an existing PC (from our initial offer), close it to re-negotiate
                    if (this.pc) {
                        this.logDebug('Closing initial negotiation for glare cleanup');
                        this.stopCalling();
                    }
                    
                    this.performAction('answerCall');
                }
            });

            // Trigger outbound call flow once server confirms eligibility
            window.addEventListener('trigger-sdp-offer', event => {
                console.log('CallOverlay: generating SDP offer...', event.detail);
                this.handleOutboundCall(event.detail);
            });

            // Handle remote answer from server (e.g. for cross-device sync)
            window.addEventListener('set-remote-answer', async event => {
                const sdp = event.detail.sdp;
                this.logDebug('Event: set-remote-answer received');
                if (this.pc && sdp && this.pc.signalingState === 'have-local-offer') {
                    this.logDebug('Applying remote answer from server to peer connection...');
                    try {
                        const sanitizedAnswer = sdp
                            .replace(/[^\x20-\x7E\r\n]/g, '')
                            .replace(/\r\n|\r|\n/g, '\n')
                            .split('\n').map(l => l.trim()).join('\r\n') + '\r\n';

                        await this.pc.setRemoteDescription({
                            type: 'answer',
                            sdp: sanitizedAnswer
                        });
                        this.logDebug('Remote answer applied SUCCESSFULLY', 'success');
                    } catch (e) {
                        this.logDebug(`FAILED to apply remote answer: ${e.message}`, 'error');
                    }
                } else if (this.pc) {
                    this.logDebug(`Ignored remote answer. PC state: ${this.pc.signalingState}`, 'warn');
                }
            });

            // Initial state check for recovery (e.g. after refresh)
            if (this.status === 'active') {
                this.logDebug('Recovery: Call is already ACTIVE');
                this.startTimer();
            } else if (this.status === 'ringing' && this.direction === 'inbound') {
                this.logDebug('Recovery: Call is RINGING');
                this.playRinging();
            }
        },
        
        async handleOutboundCall(data) {
            if (this.isProcessing) return;
            this.isProcessing = true;
            this.status = 'ringing'; 
            this.direction = 'outbound';

            try {
                // 1. Get Microphone Access & Generate Initial Offer (Required by Meta API)
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    } 
                });
                
                const iceServers = [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ];
                
                this.logDebug('Setting up outbound peer connection...');
                this.pc = new RTCPeerConnection({ iceServers });
                
                this.pc.onconnectionstatechange = () => this.logDebug(`PC Connection State: ${this.pc.connectionState}`);
                this.pc.onsignalingstatechange = () => this.logDebug(`PC Signaling State: ${this.pc.signalingState}`);
                
                stream.getTracks().forEach(track => {
                    this.logDebug(`Adding local track: ${track.kind}`);
                    this.pc.addTrack(track, stream);
                });
                
                const offer = await this.pc.createOffer({ offerToReceiveAudio: true });
                await this.pc.setLocalDescription(offer);
                
                const sanitizedOffer = offer.sdp
                    .replace(/[^\x20-\x7E\r\n]/g, '')
                    .replace(/\r\n|\r|\n/g, '\n')
                    .split('\n').map(l => l.trim()).join('\r\n') + '\r\n';

                // 2. Initiate Call with SDP
                await $wire.initiateWhatsAppCall(
                    data.phone_number, 
                    data.contact_id,
                    sanitizedOffer
                );
                
                // Handle remote tracks for the initial peer connection
                this.pc.ontrack = (event) => {
                     this.remoteStream = event.streams[0];
                     const remoteAudio = document.getElementById('remote-audio');
                     if (remoteAudio) remoteAudio.srcObject = this.remoteStream;
                };

            } catch (e) {
                console.error('Outbound call setup failed:', e);
                $wire.handleFailed({ message: 'Could not access microphone or setup call: ' + e.message });
                this.stopCalling();
            } finally {
                this.isProcessing = false;
            }
        },
        playRinging() {
            if (this.ringingSound) {
                this.ringingSound.currentTime = 0;
                this.ringingSound.play().catch(e => console.log('Audio autoplay blocked:', e));
            }
        },
        stopRinging() {
            if (this.ringingSound) {
                this.ringingSound.pause();
                this.ringingSound.currentTime = 0;
            }
        },
        
        startTimer() {
            if (this.timer) clearInterval(this.timer);
            
            // Calculate initial duration based on startTime entanglement
            if (this.startTime) {
                const now = Math.floor(Date.now() / 1000);
                this.duration = Math.max(0, now - this.startTime);
            } else {
                this.duration = 0;
            }

            this.timer = setInterval(() => {
                this.duration++;
            }, 1000);

            // Explicitly play remote audio for mobile browsers which might block autoplay
            const remoteAudio = document.getElementById('remote-audio');
            if (remoteAudio && remoteAudio.srcObject) {
                remoteAudio.play().catch(e => console.log('Remote audio play failed:', e));
            }
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
            
            // Pre-check microphone for entering active calls
            if (action === 'answerCall') {
                try {
                    const permission = await navigator.permissions.query({ name: 'microphone' });
                    if (permission.state === 'denied') {
                        $dispatch('notify', { type: 'error', message: 'Microphone permission denied. Please enable it in browser settings.' });
                        return;
                    }
                } catch(e) { /* Falls back to usual error handling in handleAnswer */ }
            }

            this.isProcessing = true;
            
            try {
                if (action === 'answerCall') {
                    await this.handleAnswer();
                } else {
                    await $wire[action]();
                }
            } finally {
                this.isProcessing = false;
            }
        },

        async handleAnswer() {
            try {
                // Optimized STUN/TURN configuration for faster NAT traversal
                const iceServers = [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { urls: 'stun:stun2.l.google.com:19302' },
                    { urls: 'stun:stun3.l.google.com:19302' },
                    { urls: 'stun:stun4.l.google.com:19302' },
                    { urls: 'stun:stun.voiparound.com' },
                    { urls: 'stun:stun.voipstunt.com' }
                ];

                this.logDebug('Setting up answer peer connection...');
                this.pc = new RTCPeerConnection({
                    iceServers: iceServers,
                    iceCandidatePoolSize: 20, 
                    bundlePolicy: 'max-bundle',
                    rtcpMuxPolicy: 'require'
                });

                this.pc.onconnectionstatechange = () => this.logDebug(`PC Connection State: ${this.pc.connectionState}`);
                this.pc.onsignalingstatechange = () => this.logDebug(`PC Signaling State: ${this.pc.signalingState}`);
                this.pc.onicegatheringstatechange = () => this.logDebug(`ICE Gathering State: ${this.pc.iceGatheringState}`);

                // Track ICE candidates
                let iceCandidatesCount = 0;
                let connectionType = null;

                this.pc.onicecandidate = (event) => {
                    if (event.candidate) {
                        iceCandidatesCount++;
                        console.log('ICE candidate gathered:', event.candidate.type);
                    }
                };

                // Monitor connection state
                this.pc.onconnectionstatechange = () => {
                    console.log('Connection state:', this.pc.connectionState);
                    
                    if (this.pc.connectionState === 'connected') {
                        // Record connection established
                        $wire.recordConnectionEstablished(iceCandidatesCount, connectionType);
                    } else if (this.pc.connectionState === 'failed') {
                        $dispatch('notify', { 
                            type: 'error', 
                            message: 'Call connection failed. Please try again.' 
                        });
                        this.stopCalling();
                    } else if (this.pc.connectionState === 'disconnected') {
                        $dispatch('notify', { 
                            type: 'warning', 
                            message: 'Call connection lost.' 
                        });
                    }
                };

                // Monitor ICE connection state
                this.pc.oniceconnectionstatechange = () => {
                    console.log('ICE connection state:', this.pc.iceConnectionState);
                    
                    if (this.pc.iceConnectionState === 'connected' || 
                        this.pc.iceConnectionState === 'completed') {
                        // Monitor Signal Quality periodically
                        const statsInterval = setInterval(() => {
                            if (!this.pc || this.pc.connectionState !== 'connected') {
                                clearInterval(statsInterval);
                                return;
                            }

                            this.pc.getStats().then(stats => {
                                stats.forEach(report => {
                                    if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                                        this.connectionType = report.currentRoundTripTime ? 'relay' : 'direct';
                                        // Simple quality metric based on RTT (if available)
                                        if (report.currentRoundTripTime) {
                                            const rtt = report.currentRoundTripTime * 1000;
                                            this.signalQuality = Math.max(0, 100 - (rtt / 5)); // Penalty for higher RTT
                                        }
                                    }
                                });
                            });
                        }, 2000);
                    }
                };

                this.pc.ontrack = (event) => {
                    this.logDebug(`Remote track received: ${event.track.kind}`, 'success');
                    this.remoteStream = event.streams[0];
                    const remoteAudio = document.getElementById('remote-audio');
                    if (remoteAudio) {
                        remoteAudio.srcObject = this.remoteStream;
                        remoteAudio.play().catch(e => this.logDebug(`Early audio play failed: ${e.message}`, 'warn'));
                    }
                };

                // High-fidelity business audio constraints
                const localStream = await navigator.mediaDevices.getUserMedia({ 
                    audio: {
                        echoCancellation: { ideal: true },
                        noiseSuppression: { ideal: true },
                        autoGainControl: { ideal: true },
                        channelCount: 1,
                        sampleRate: 48000,
                        sampleSize: 16
                    } 
                });
                localStream.getTracks().forEach(track => this.pc.addTrack(track, localStream));

                // Set remote description (the offer from Meta)
                if (!this.offerSdp) {
                    throw new Error('No offer SDP available to answer.');
                }

                // Robust SDP sanitization for the browser
                const sanitizedOffer = this.offerSdp
                    .replace(/[^\x20-\x7E\r\n]/g, '') // Remove non-printable
                    .replace(/\r\n|\r|\n/g, '\n')     // Normalize to \n
                    .split('\n')
                    .map(line => line.trim())
                    .filter(line => line.length > 0)
                    .join('\r\n') + '\r\n';

                await this.pc.setRemoteDescription({
                    type: 'offer',
                    sdp: sanitizedOffer
                });

                const answer = await this.pc.createAnswer();
                await this.pc.setLocalDescription(answer);

                // IMPORTANT: Wait for ICE gathering to complete (or timeout) before sending answer
                // Meta Cloud API often requires candidates in the initial answer for stable media
                this.logDebug(`ICE Gathering state: ${this.pc.iceGatheringState}`);
                await new Promise(resolve => {
                    if (this.pc.iceGatheringState === 'complete') {
                        resolve();
                    } else {
                        const checkState = () => {
                            if (this.pc.iceGatheringState === 'complete') {
                                this.logDebug('ICE Gathering COMPLETE', 'success');
                                this.pc.removeEventListener('icegatheringstatechange', checkState);
                                resolve();
                            }
                        };
                        this.pc.addEventListener('icegatheringstatechange', checkState);
                        // Max wait for 2.5 seconds of gathering to avoid stalling
                        setTimeout(() => {
                            this.logDebug(`ICE Gathering partial (state: ${this.pc.iceGatheringState})`, 'warn');
                            resolve();
                        }, 2500);
                    }
                });

                // Get the final SDP with gathered candidates
                const finalAnswer = this.pc.localDescription;

                // Implement timeout for connection establishment
                const connectionTimeout = setTimeout(() => {
                    if (this.pc && this.pc.connectionState !== 'connected') {
                        console.warn('Call connection taking longer than expected...');
                    }
                }, 15000);

                // Send sanitized final answer to backend
                const sanitizedAnswer = finalAnswer.sdp
                    .replace(/[^\x20-\x7E\r\n]/g, '')
                    .replace(/\r\n|\r|\n/g, '\n')
                    .split('\n').map(l => l.trim()).join('\r\n') + '\r\n';

                this.logDebug('Sending Answer SDP to Meta via Backend...');
                await $wire.answerCall(sanitizedAnswer);
                this.logDebug('Backend answerCall request SENT', 'success');
                
                clearTimeout(connectionTimeout);

            } catch (error) {
                console.error('Failed to answer call:', error);
                
                // Provide user-friendly error messages
                let errorMessage = 'Failed to answer call: ';
                if (error.name === 'NotAllowedError') {
                    errorMessage += 'Microphone access denied. Please allow microphone access and try again.';
                } else if (error.name === 'NotFoundError') {
                    errorMessage += 'No microphone found. Please connect a microphone and try again.';
                } else if (error.message.includes('timeout')) {
                    errorMessage += 'Connection timeout. Please check your internet connection.';
                } else if (error.message.includes('SDP')) {
                    errorMessage += 'Invalid call data. Please try again.';
                } else {
                    errorMessage += error.message;
                }
                
                $dispatch('notify', { type: 'error', message: errorMessage });
                this.stopCalling();
                throw error;
            }
        },

        stopCalling() {
            if (this.pc) {
                this.pc.close();
                this.pc = null;
            }
            if (this.remoteStream) {
                this.remoteStream.getTracks().forEach(track => track.stop());
                this.remoteStream = null;
            }
            const remoteAudio = document.getElementById('remote-audio');
            if (remoteAudio) {
                remoteAudio.srcObject = null;
            }
        }
    }" @auto-hide-overlay.window="setTimeout(() => $wire.resetOverlay(), 3000)" @call-stopped.window="stopCalling()"
    @play-ringing-sound.window="playRinging()"
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
        <audio id="call-ringing-sound" loop preload="auto">
            <source src="https://assets.mixkit.co/active_storage/sfx/1359/1359-preview.mp3" type="audio/mpeg">
        </audio>

        <audio id="remote-audio" autoplay></audio>

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

            <!-- Middle/Right: Duration -->
            <div x-show="status === 'active'" class="flex flex-col items-center">
                <span class="text-lg font-mono font-black text-white tracking-tighter"
                    x-text="formatDuration(duration)">0:00</span>
                <span class="text-[8px] font-black text-white/50 uppercase tracking-[0.2em]">Duration</span>
            </div>

            <!-- Signal Bars & Debug Toggle -->
            <div class="absolute top-4 right-4 flex flex-col items-end gap-2">
                <!-- Status & Quality -->
                <div class="flex flex-col items-center">
                    <div class="flex items-end gap-0.5 h-4 mb-0.5">
                        <template x-for="i in 4" :key="i">
                            <div class="w-1.5 rounded-full transition-all duration-500" :class="{
                                'bg-white shadow-[0_0_8px_rgba(255,255,255,0.5)]': signalQuality >= (i * 25),
                                'bg-white/20': signalQuality < (i * 25)
                            }" :style="{ height: (i * 25) + '%' }"></div>
                        </template>
                    </div>
                    <span class="text-[8px] font-black text-white/50 uppercase tracking-[0.2em]"
                        x-text="pc ? pc.connectionState : (connectionType || 'IDLE')"></span>
                </div>

                <!-- Debug Toggle -->
                <button @click="showDebug = !showDebug"
                    class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors pointer-events-auto">
                    <svg class="w-3 h-3 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </button>
            </div>

            <!-- Visual Debug Console -->
            <template x-if="showDebug">
                <div wire:ignore x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-10 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-10 scale-95"
                    class="absolute bottom-24 left-4 right-4 max-h-40 overflow-y-auto bg-black/80 backdrop-blur-xl rounded-xl p-3 border border-white/10 font-mono text-[10px] text-indigo-300 pointer-events-auto z-[110]">
                    <div class="flex justify-between items-center mb-2 border-b border-white/10 pb-1">
                        <span class="font-bold text-white/70 uppercase tracking-wider">Signals & Events</span>
                        <button @click="debugLogs = []" class="text-white/40 hover:text-white">Clear</button>
                    </div>
                    <div class="space-y-1">
                        <template x-for="logEntry in debugLogs" :key="logEntry.id">
                            <div class="flex gap-2">
                                <span class="text-white/30" x-text="logEntry?.time || ''"></span>
                                <span x-text="logEntry?.msg || ''" :class="{
                                    'text-green-400': logEntry?.type === 'success',
                                    'text-red-400': logEntry?.type === 'error',
                                    'text-amber-400': logEntry?.type === 'warn',
                                    'text-indigo-300': logEntry?.type === 'info'
                                }"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2">
                <template x-if="status === 'ringing' && direction === 'inbound'">
                    <div class="flex items-center gap-2">
                        <!-- Reject Button -->
                        <button @click="performAction('rejectCall')"
                            class="p-3 rounded-2xl bg-rose-500 hover:bg-rose-600 text-white transition-all duration-300 transform hover:scale-110 active:scale-90 shadow-lg pointer-events-auto"
                            :disabled="isProcessing">
                            <svg class="w-5 h-5 rotate-[135deg]" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M21 15.46l-5.27-.61-2.52 2.52c-2.83-1.44-5.15-3.75-6.59-6.59l2.53-2.53L8.54 3H3.01C2.45 13.18 10.82 21.55 21 20.99v-5.53z" />
                            </svg>
                        </button>

                        <!-- Answer Button -->
                        <button @click="performAction('answerCall')"
                            class="p-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white transition-all duration-300 transform hover:scale-110 active:scale-90 shadow-lg animate-bounce pointer-events-auto"
                            :disabled="isProcessing">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-2.2 2.2c-2.83-1.44-5.15-3.75-6.59-6.59l2.2-2.21c.28-.26.36-.65.25-1.01A11.332 11.332 0 018.58 4c0-.55-.45-1-1-1H4.11c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.62c0-.55-.45-1-1-1z" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- End Call Button (Show for active calls or outbound ringing) -->
                <template
                    x-if="status === 'active' || (status === 'ringing' && direction === 'outbound') || status === 'ended'">
                    <button @click="performAction('endCall')"
                        class="group p-3 rounded-2xl transition-all duration-300 transform hover:scale-110 active:scale-90 shadow-lg pointer-events-auto"
                        :class="(status === 'ended' || isProcessing || isLocked) ? 'bg-white/10 text-white/20 cursor-not-allowed' : 'bg-rose-500 hover:bg-rose-600 text-white'">
                        <svg x-show="!isProcessing" class="w-5 h-5 rotate-[135deg]" fill="currentColor"
                            viewBox="0 0 24 24">
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