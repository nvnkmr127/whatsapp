export default {
    messages: [],
    conversationId: null,
    loading: false,
    hasMore: true,
    wire: null,

    initMessages(wire, conversationId, teamId, initialMessages = null) {
        Object.defineProperty(this, '_wire', {
            value: wire,
            writable: true,
            enumerable: false,
            configurable: true
        });
        this.wire = wire;
        this.conversationId = conversationId;
        this.teamId = teamId;
        this.messages = [];
        this.hasMore = true;

        if (!this._hasMsgListeners) {
            window.addEventListener('messageSent', () => {
                this.loadMessages(true);
            });
            this._hasMsgListeners = true;
        }

        // The first page rides along in the initial render — no extra roundtrip.
        if (Array.isArray(initialMessages)) {
            this.messages = initialMessages; // already chronological from loadMessagesJson()
            this.hasMore = initialMessages.length >= 50;
            window.dispatchEvent(new CustomEvent('chat-initial-loaded'));
            return;
        }

        this.loadMessages(true);
    },

    async loadMessages(isInitial = false) {
        const wire = this._wire || this.wire;
        if (!wire || this.loading || (!this.hasMore && !isInitial)) return;
        this.loading = true;

        try {
            const offset = isInitial ? 0 : this.messages.length;
            const newBatch = await wire.call('loadMessagesJson', offset, 50);

            if (newBatch.length < 50) {
                this.hasMore = false;
            }

            if (isInitial) {
                this.messages = newBatch;
                window.dispatchEvent(new CustomEvent('chat-initial-loaded'));
            } else {
                this.messages = [...newBatch, ...this.messages];
            }
        } catch (error) {
            console.error('Failed to load messages', error);
        } finally {
            this.loading = false;
        }
    },

    async sendMessage(body) {
        const tempId = 'temp_' + Date.now();
        const wire = this._wire || this.wire;
        const replyToId = wire ? wire.replyToMessageId : null;
        let replyObj = null;
        if (replyToId) {
            const original = this.messages.find(m => m.id === replyToId);
            if (original) {
                replyObj = {
                    content: original.content || (original.type ? original.type.charAt(0).toUpperCase() + original.type.slice(1) : ''),
                    is_outbound: original.is_outbound
                };
            }
        }

        const optimisticMsg = {
            id: tempId,
            direction: 'outbound',
            content: body,
            type: 'text',
            status: 'sending',
            created_at: Math.floor(Date.now() / 1000),
            pretty_time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            is_outbound: true,
            media_url: null,
            reply_to_message_id: replyToId,
            reply_to_message: replyObj
        };

        this.messages.push(optimisticMsg);
        window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));

        try {
            const wire = this._wire || this.wire;
            const result = await wire.call('sendMessageJson', body, tempId);

            if (result.status === 'error') {
                throw new Error(result.message);
            }

            const index = this.messages.findIndex(m => m.id === tempId);
            if (index !== -1) {
                this.messages[index] = { ...this.messages[index], ...result };
            }
        } catch (e) {
            console.error('Send failed', e);
            const index = this.messages.findIndex(m => m.id === tempId);
            if (index !== -1) {
                this.messages[index].status = 'failed';
                this.messages[index].error_message = e.message;
            }
        }
    },

    async retryMessage(id) {
        const index = this.messages.findIndex(m => m.id === id);
        if (index === -1) return;

        const body = this.messages[index].content;
        this.messages[index].status = 'sending';

        try {
            const wire = this._wire || this.wire;
            const result = await wire.call('sendMessageJson', body, id);

            if (result.status === 'error') {
                throw new Error(result.message);
            }

            this.messages[index] = { ...this.messages[index], ...result };
        } catch (e) {
            console.error('Retry failed', e);
            this.messages[index].status = 'failed';
            this.messages[index].error_message = e.message;
            window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', message: e.message } }));
        }
    },

    receiveMessage(msg) {
        const index = this.messages.findIndex(m => m.id === msg.id);
        if (index !== -1) {
            // Update existing message (e.g., when media finishes downloading)
            this.messages[index] = { ...this.messages[index], ...msg };
            return;
        }
        
        this.messages.push(msg);
        this.messages.sort((a, b) => a.created_at - b.created_at);
        window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));

        // Play sound if it's an inbound message or if it's from another agent
        if (msg.direction === 'inbound' || (msg.is_outbound && msg.agent_id !== this.myUserId)) {
            this.playNotificationSound();
        }
    },

    _audioPlaying: false,
    playNotificationSound() {
        if (this._audioPlaying) return;
        this._audioPlaying = true;
        const audio = new Audio('/sounds/notification.mp3');
        audio.onended = () => { this._audioPlaying = false; };
        audio.onerror = () => { this._audioPlaying = false; };
        audio.play().catch(() => { this._audioPlaying = false; });
    },

    getDateLabel(timestamp) {
        const ts = typeof timestamp === 'number' ? timestamp * 1000 : Date.parse(timestamp);
        if (isNaN(ts)) return '';

        const date = new Date(ts);
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        if (date.toDateString() === today.toDateString()) return 'Today';
        if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';

        return date.toLocaleDateString([], { day: 'numeric', month: 'long', year: 'numeric' });
    },

    shouldShowDateDivider(index) {
        if (index === 0) return true;
        const current = this.messages[index];
        const previous = this.messages[index - 1];

        if (!current || !previous || !current.created_at || !previous.created_at) return false;

        return this.getDateLabel(current.created_at) !== this.getDateLabel(previous.created_at);
    },

    async syncLatest() {
        const wire = this._wire || this.wire;
        if (!wire || !this.conversationId) return;

        try {
            const latestBatch = await wire.call('loadMessagesJson', 0, 20);

            let addedCount = 0;
            latestBatch.forEach(newMsg => {
                const idx = this.messages.findIndex(m => m.id === newMsg.id);
                if (idx === -1) {
                    this.messages.push(newMsg);
                    addedCount++;
                } else {
                    if (this.messages[idx].status !== newMsg.status) {
                        this.messages[idx].status = newMsg.status;
                    }
                    if (newMsg.media_url && this.messages[idx].media_url !== newMsg.media_url) {
                        this.messages[idx].media_url = newMsg.media_url;
                        this.messages[idx].media_type = newMsg.media_type;
                    }
                }
            });

            if (addedCount > 0) {
                // Sort chronologically; handle mixed number/string timestamps
                this.messages.sort((a, b) => {
                    const ta = typeof a.created_at === 'number' ? a.created_at : Date.parse(a.created_at) / 1000;
                    const tb = typeof b.created_at === 'number' ? b.created_at : Date.parse(b.created_at) / 1000;
                    return ta - tb;
                });
                window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));
            }
        } catch (e) {
            console.error('Sync failed', e);
        }
    }
};
