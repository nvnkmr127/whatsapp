export default (wire, conversationId, teamId, userId) => ({
    itemHeight: 72,
    buffer: 15,
    viewportHeight: 0,
    scrollTop: 0,
    msgBody: '',
    showAttach: false,
    showEmoji: false,
    showQR: false,
    qrFilter: '',
    showTransferModal: null,
    showInteractiveButtonsModal: null,
    isNoteMode: false,
    lightboxOpen: false,
    lightboxImage: '',
    isRecording: false,
    recordingTime: '0:00',
    mediaRecorder: null,
    audioChunks: [],
    shouldSendRecording: false,
    recInterval: null,
    _submitting: false,
    _boundHandlers: null,

    init() {
        this.$store.chat.setMyUser(userId);
        this.$store.chat.init(wire, conversationId, teamId);

        this.showTransferModal = wire.entangle('showTransferModal');
        this.showInteractiveButtonsModal = wire.entangle('showInteractiveButtonsModal');
        this.isNoteMode = wire.entangle('isNoteMode');
        this.lightboxOpen = wire.entangle('lightboxOpen');
        this.lightboxImage = wire.entangle('lightboxImage');

        // Store bound handler references so we can remove them on destroy
        this._boundHandlers = {
            dragover: (e) => { e.preventDefault(); this.$store.chat.isDragging = true; },
            dragleave: (e) => { e.preventDefault(); if (e.relatedTarget === null) this.$store.chat.isDragging = false; },
            drop: (e) => {
                e.preventDefault();
                this.$store.chat.isDragging = false;
                if (e.dataTransfer?.files?.length > 0) {
                    const file = e.dataTransfer.files[0];
                    const maxBytes = 16 * 1024 * 1024; // 16 MB
                    if (file.size > maxBytes) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'File too large (max 16 MB)', type: 'error' } }));
                        return;
                    }
                    wire.upload('newAttachment', file);
                }
            },
            scrollBottom: () => this.scrollToBottom(),
            initialLoaded: () => this.scrollToBottom(),
            updateBody: (e) => {
                this.msgBody = e.detail.body;
                if (this.$refs.messageInput) this.$refs.messageInput.focus();
            },
        };

        window.addEventListener('dragover', this._boundHandlers.dragover);
        window.addEventListener('dragleave', this._boundHandlers.dragleave);
        window.addEventListener('drop', this._boundHandlers.drop);
        window.addEventListener('chat-scroll-bottom', this._boundHandlers.scrollBottom);
        window.addEventListener('chat-initial-loaded', this._boundHandlers.initialLoaded);
        window.addEventListener('update-message-body', this._boundHandlers.updateBody);

        this.viewportHeight = this.$el.clientHeight;

        this.$watch('$store.chat.messages', (val, old) => {
            if (old.length === 0 && val.length > 0) {
                this.$nextTick(() => this.scrollToBottom());
            }
        });
    },

    destroy() {
        if (this._boundHandlers) {
            window.removeEventListener('dragover', this._boundHandlers.dragover);
            window.removeEventListener('dragleave', this._boundHandlers.dragleave);
            window.removeEventListener('drop', this._boundHandlers.drop);
            window.removeEventListener('chat-scroll-bottom', this._boundHandlers.scrollBottom);
            window.removeEventListener('chat-initial-loaded', this._boundHandlers.initialLoaded);
            window.removeEventListener('update-message-body', this._boundHandlers.updateBody);
            this._boundHandlers = null;
        }
        if (this.recInterval) clearInterval(this.recInterval);
        this.$store.chat.stopHeartbeat?.();
    },

    scrollToBottom() {
        if (this.$refs.chatContainer) {
            this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
        }
    },

    handleScroll(e) {
        this.scrollTop = e.target.scrollTop;
        if (this.scrollTop < 100 && this.$store.chat.messages.length > 0 && !this.$store.chat.loading) {
            const oldHeight = this.$el.scrollHeight;
            const oldTop = this.$el.scrollTop;
            this.$store.chat.loadMessages().then(() => {
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
        if (!this.$store.chat || !this.$store.chat.messages) {
            return { start: 0, end: 0, top: 0, bottom: 0 };
        }

        const count = this.$store.chat.messages.length;

        if (count < 100) return { start: 0, end: count, top: 0, bottom: 0 };

        let start = Math.max(0, this.startIndex - this.buffer);
        let visibleCount = Math.ceil(this.viewportHeight / this.itemHeight) + (2 * this.buffer);
        let end = Math.min(count, start + visibleCount);

        return { start, end, top: start * this.itemHeight, bottom: (count - end) * this.itemHeight };
    },

    get visibleMessages() {
        if (!this.$store.chat || !this.$store.chat.messages) return [];
        const conf = this.renderConfig;
        return this.$store.chat.messages.slice(conf.start, conf.end);
    },

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
        if (this.$refs.messageInput) this.$refs.messageInput.focus();
    },

    insertEmoji(emoji) {
        this.msgBody = (this.msgBody || '') + emoji;
        this.showEmoji = false;
    },

    async handleSubmit() {
        if (this._submitting) return;
        if (this.msgBody.trim() === '' && !wire.newAttachment) return;

        this._submitting = true;
        try {
            if (this.isNoteMode) {
                wire.set('messageBody', this.msgBody);
                try {
                    await wire.saveInternalNote();
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Failed to save note. Please try again.', type: 'error' } }));
                    return;
                }
                this.msgBody = '';
                this.isNoteMode = false;
                return;
            }

            if (wire.newAttachment) {
                wire.set('messageBody', this.msgBody);
                try {
                    await wire.sendMessage();
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Failed to send message. Please try again.', type: 'error' } }));
                    return;
                }
                this.msgBody = '';
                return;
            }

            const body = this.msgBody;
            this.msgBody = '';
            this.$store.chat.sendMessage(body);
        } finally {
            this._submitting = false;
        }
    },

    async startRecording() {
        if (!navigator.mediaDevices?.getUserMedia) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Microphone not supported in this browser.', type: 'error' } }));
            return;
        }

        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (err) {
            const msg = err.name === 'NotAllowedError'
                ? 'Microphone permission denied. Please allow access in your browser settings.'
                : 'Could not access microphone: ' + err.message;
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: msg, type: 'error' } }));
            return;
        }

        this.mediaRecorder = new MediaRecorder(stream);
        this.audioChunks = [];

        this.mediaRecorder.ondataavailable = (e) => {
            this.audioChunks.push(e.data);
        };

        this.mediaRecorder.onstop = async () => {
            stream.getTracks().forEach(track => track.stop());
            const audioBlob = new Blob(this.audioChunks, { type: 'audio/ogg; codecs=opus' });
            if (this.shouldSendRecording) {
                wire.upload('newAttachment', audioBlob, (uploadedFilename) => {
                    wire.sendVoiceNote(uploadedFilename);
                });
            }
        };

        this.mediaRecorder.start();
        this.isRecording = true;
        this.shouldSendRecording = false;

        let sec = 0;
        this.recInterval = setInterval(() => {
            sec++;
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            this.recordingTime = `${m}:${s < 10 ? '0' : ''}${s}`;
        }, 1000);
    },

    stopRecording(send = true) {
        this.isRecording = false;
        this.shouldSendRecording = send;
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
        }
        clearInterval(this.recInterval);
        this.recInterval = null;
    }
});
