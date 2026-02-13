export default (wire, conversationId, teamId, userId) => ({
    itemHeight: 80, // Average height estimate
    buffer: 5,
    viewportHeight: 0,
    scrollTop: 0,
    msgBody: '',
    showAttach: false,
    showEmoji: false,
    showQR: false,
    qrFilter: '',
    isNoteMode: false,
    isRecording: false,
    recordingTime: '0:00',
    mediaRecorder: null,
    audioChunks: [],
    shouldSendRecording: false,
    recInterval: null,

    init() {
        // Initialize Store
        this.$store.chat.setMyUser(userId);
        this.$store.chat.init(wire, conversationId, teamId);

        // Drag and Drop Listeners
        window.addEventListener('dragover', (e) => { e.preventDefault(); this.$store.chat.isDragging = true; });
        window.addEventListener('dragleave', (e) => { e.preventDefault(); if (e.relatedTarget === null) this.$store.chat.isDragging = false; });
        window.addEventListener('drop', (e) => {
            e.preventDefault();
            this.$store.chat.isDragging = false;
            if (e.dataTransfer.files.length > 0) {
                wire.upload('newAttachment', e.dataTransfer.files[0]);
            }
        });

        this.viewportHeight = this.$el.clientHeight;

        // Debug logs
        console.log('MessageWindow: Init', {
            convId: conversationId,
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
            console.log('MessageWindow: Initial Load Complete', this.$store.chat.messages);
            this.scrollToBottom();
        });

        window.addEventListener('update-message-body', (e) => {
            this.msgBody = e.detail.body;
            if (this.$refs.messageInput) this.$refs.messageInput.focus();
        });
    },

    scrollToBottom() {
        if (this.$refs.chatContainer) {
            this.$refs.chatContainer.scrollTop = this.$refs.chatContainer.scrollHeight;
        }
    },

    handleScroll(e) {
        this.scrollTop = e.target.scrollTop;
        // Load More Trigger
        if (this.scrollTop < 100 && this.$store.chat.messages.length > 0) {
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

        let topH = start * this.itemHeight;
        let bottomH = (count - end) * this.itemHeight;

        return { start, end, top: topH, bottom: bottomH };
    },

    get visibleMessages() {
        if (!this.$store.chat || !this.$store.chat.messages) {
            return [];
        }
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
        if (this.msgBody.trim() === '' && !wire.newAttachment) return;

        // Handle Internal Note
        if (this.isNoteMode) {
            wire.set('messageBody', this.msgBody);
            await wire.saveInternalNote();
            this.msgBody = '';
            this.isNoteMode = false;
            return;
        }

        // Check for attachment (Legacy Path)
        if (wire.newAttachment) {
            wire.set('messageBody', this.msgBody);
            await wire.sendMessage(); // Legacy
            this.msgBody = '';
            return;
        }

        // Text Only (Optimistic Path)
        const body = this.msgBody;
        this.msgBody = ''; // Clear immediately
        this.$store.chat.sendMessage(body);
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
                    wire.upload('newAttachment', audioBlob, (uploadedFilename) => {
                        wire.sendVoiceNote(uploadedFilename);
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
});
