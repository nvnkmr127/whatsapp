export default (wire, conversationId, teamId, userId, showTransferModal, showInteractiveButtonsModal, isNoteMode, lightboxOpen, lightboxImage, quickReplies, initialMessages = null) => ({
    itemHeight: 72,
    buffer: 15,
    viewportHeight: 0,
    scrollTop: 0,
    msgBody: '',
    showAttach: false,
    showEmoji: false,
    showQR: false,
    qrFilter: '',
    showTransferModal,
    showInteractiveButtonsModal,
    isNoteMode,
    lightboxOpen,
    lightboxImage,
    quickReplies,
    isRecording: false,
    recordingTime: '0:00',
    mediaRecorder: null,
    audioChunks: [],
    shouldSendRecording: false,
    recInterval: null,
    _submitting: false,
    _boundHandlers: null,
    hasAttachment: false,
    attachmentName: '',
    attachmentPreview: null,

    isNearBottom: true,
    unreadBelowCount: 0,
    showShortcutsModal: false,

    get canSend() {
        return !!(this.msgBody.trim() || this.hasAttachment);
    },

    // Local state flips instantly so the send button appears while the upload is
    // still in flight; $wire.newAttachmentData only lands a roundtrip later.
    onFilePicked(file) {
        if (!file) return;
        if (file.size > 16 * 1024 * 1024) {
            this.$refs.fileInput.value = '';
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'File too large (max 16 MB)', type: 'error' } }));
            return;
        }
        this.hasAttachment = true;
        this.attachmentName = file.name || 'attachment';
        this.attachmentPreview = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
    },

    clearAttachment(serverToo = true) {
        if (this.attachmentPreview) URL.revokeObjectURL(this.attachmentPreview);
        this.hasAttachment = false;
        this.attachmentName = '';
        this.attachmentPreview = null;
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        if (serverToo) wire.deleteAttachment();
    },

    // The upload finishes on its own roundtrip; sendMessage() bails out if the
    // path isn't there yet, so wait for it rather than silently dropping the send.
    async waitForUpload(timeoutMs = 60000) {
        const deadline = Date.now() + timeoutMs;
        while (!wire.newAttachmentData && Date.now() < deadline) {
            if (wire.uploadError) return false;
            await new Promise(r => setTimeout(r, 150));
        }
        return !!wire.newAttachmentData;
    },

    init() {
        this.$store.chat.setMyUser(userId);
        this.$store.chat.init(wire, conversationId, teamId, initialMessages);

        // Restore draft for this conversation
        const existingDraft = this.$store.chat.getDraft(conversationId);
        if (existingDraft) {
            this.msgBody = existingDraft;
        }

        this.$watch('msgBody', (val) => {
            this.$store.chat.saveDraft(conversationId, val);
            this.checkQR();
        });

        // Store bound handler references so we can remove them on destroy
        this._boundHandlers = {
            dragover: (e) => { e.preventDefault(); this.$store.chat.isDragging = true; },
            dragleave: (e) => { e.preventDefault(); if (e.relatedTarget === null) this.$store.chat.isDragging = false; },
            drop: (e) => {
                e.preventDefault();
                this.$store.chat.isDragging = false;
                if (e.dataTransfer?.files?.length > 0) {
                    const file = e.dataTransfer.files[0];
                    this.onFilePicked(file);
                    if (this.hasAttachment) wire.upload('newAttachment', file);
                }
            },
            scrollBottom: () => {
                if (this.isNearBottom) {
                    this.scrollToBottom();
                } else {
                    this.unreadBelowCount++;
                }
            },
            initialLoaded: () => this.scrollToBottom(),
            updateBody: (e) => {
                this.msgBody = e.detail.body;
                if (this.$refs.messageInput) this.$refs.messageInput.focus();
            },
            scrollToId: (e) => {
                const id = e.detail.id;
                const el = document.getElementById('message-' + id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Highlight exactly like WhatsApp Web
                    el.classList.add('bg-wa-teal/20', 'dark:bg-wa-teal/30', '-mx-4', 'px-4');
                    setTimeout(() => {
                        el.classList.remove('bg-wa-teal/20', 'dark:bg-wa-teal/30', '-mx-4', 'px-4');
                    }, 1500);
                }
            },
            keydown: (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    this.handleSubmit();
                } else if (e.altKey && (e.key === 'n' || e.key === 'N')) {
                    e.preventDefault();
                    this.isNoteMode = true;
                } else if (e.altKey && (e.key === 'm' || e.key === 'M')) {
                    e.preventDefault();
                    this.isNoteMode = false;
                } else if ((e.key === '?' && e.shiftKey) || ((e.ctrlKey || e.metaKey) && e.key === '/')) {
                    e.preventDefault();
                    this.showShortcutsModal = !this.showShortcutsModal;
                } else if (e.key === 'Escape') {
                    if (this.showShortcutsModal) {
                        this.showShortcutsModal = false;
                    } else if (this.lightboxOpen) {
                        this.lightboxOpen = false;
                    } else if (this.showQR) {
                        this.showQR = false;
                    } else if (wire.replyToMessageId) {
                        wire.set('replyToMessageId', null);
                    }
                }
            },
        };

        window.addEventListener('dragover', this._boundHandlers.dragover);
        window.addEventListener('dragleave', this._boundHandlers.dragleave);
        window.addEventListener('drop', this._boundHandlers.drop);
        window.addEventListener('chat-scroll-bottom', this._boundHandlers.scrollBottom);
        window.addEventListener('chat-initial-loaded', this._boundHandlers.initialLoaded);
        window.addEventListener('update-message-body', this._boundHandlers.updateBody);
        window.addEventListener('chat-scroll-to-id', this._boundHandlers.scrollToId);
        window.addEventListener('keydown', this._boundHandlers.keydown);

        this.viewportHeight = this.$el.clientHeight;

        this.$watch('$store.chat.messages', () => {
            this.scrollToBottom();
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
            window.removeEventListener('chat-scroll-to-id', this._boundHandlers.scrollToId);
            window.removeEventListener('keydown', this._boundHandlers.keydown);
            this._boundHandlers = null;
        }
        if (this.recInterval) clearInterval(this.recInterval);
        this.$store.chat.stopHeartbeat?.();
    },

    scrollToBottom() {
        this.unreadBelowCount = 0;
        this.isNearBottom = true;
        const doScroll = () => {
            const el = this.$refs.chatContainer || this.$el;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        };
        doScroll();
        this.$nextTick(() => {
            doScroll();
            if (typeof requestAnimationFrame !== 'undefined') {
                requestAnimationFrame(() => {
                    doScroll();
                    setTimeout(doScroll, 50);
                    setTimeout(doScroll, 150);
                    setTimeout(doScroll, 300);
                });
            }
        });
    },

    handleScroll(e) {
        const el = e.target;
        this.scrollTop = el.scrollTop;
        this.isNearBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 150;
        if (this.isNearBottom) {
            this.unreadBelowCount = 0;
        }

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
        const match = val.match(/(?:^|\s)\/([^\s]*)$/);
        if (match) {
            this.showQR = true;
            this.qrFilter = match[1].toLowerCase();
        } else {
            this.showQR = false;
        }
    },

    selectQR(text) {
        const val = this.msgBody || '';
        this.msgBody = val.replace(/(?:^|\s)\/([^\s]*)$/, ' ' + text);
        this.showQR = false;
        if (this.$refs.messageInput) this.$refs.messageInput.focus();
    },

    insertEmoji(emoji) {
        this.msgBody = (this.msgBody || '') + emoji;
        this.showEmoji = false;
    },

    applyFormat(wrapper) {
        const input = this.$refs.messageInput;
        if (!input) return;
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        const val = this.msgBody || '';
        const selected = val.substring(start, end) || 'text';
        const replacement = `${wrapper}${selected}${wrapper}`;
        this.msgBody = val.substring(0, start) + replacement + val.substring(end);
        this.$nextTick(() => {
            input.focus();
            input.setSelectionRange(start + wrapper.length, start + wrapper.length + selected.length);
        });
    },

    async handleSubmit() {
        if (this._submitting) return;
        if (!this.canSend && !this.isNoteMode) return;

        this._submitting = true;
        try {
            if (this.isNoteMode) {
                wire.set('msgBody', this.msgBody);
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

            if (this.hasAttachment) {
                if (!await this.waitForUpload()) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Attachment upload failed. Please try again.', type: 'error' } }));
                    return;
                }
                wire.set('msgBody', this.msgBody);
                try {
                    await wire.sendMessage();
                } catch (e) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Failed to send message. Please try again.', type: 'error' } }));
                    return;
                }
                this.msgBody = '';
                this.clearAttachment(false);
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
                wire.upload('voiceNote', audioBlob, (uploadedFilename) => {
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
    },

    scrollToMessage(id) {
        const el = document.getElementById('message-' + id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('bg-wa-teal/20');
            setTimeout(() => {
                el.classList.remove('bg-wa-teal/20');
            }, 2000);
        }
    }
});
