document.addEventListener('alpine:init', () => {
    Alpine.store('chat', {
        messages: [],
        conversationId: null,
        loading: false,
        hasMore: true,
        wire: null, // Reference to Livewire component

        // Virtual Scroll State (Data only, view logic in component)
        totalItems() { return this.messages.length },

        init(wire, conversationId) {
            // Store wire as non-reactive to avoid Vue/Alpine markers
            Object.defineProperty(this, '_wire', {
                value: wire,
                writable: true,
                enumerable: false,
                configurable: true
            });
            this.wire = wire;
            this.conversationId = conversationId;
            this.messages = [];
            this.hasMore = true;

            // Defer initial load to ensure $wire is fully ready
            setTimeout(() => this.loadMessages(true), 100);
        },

        async loadMessages(isInitial = false) {
            const wire = this._wire || this.wire;
            if (!wire || this.loading || !this.hasMore) return;
            this.loading = true;

            try {
                // Offset is current count
                const offset = this.messages.length;
                const newBatch = await wire.call('loadMessagesJson', offset, 50);

                if (newBatch.length < 50) {
                    this.hasMore = false;
                }

                if (isInitial) {
                    this.messages = newBatch;
                    // Dispatch event for checking scroll, etc.
                    window.dispatchEvent(new CustomEvent('chat-initial-loaded'));
                } else {
                    // Prepend older messages
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
            const optimisticMsg = {
                id: tempId,
                direction: 'outbound',
                content: body,
                type: 'text',
                status: 'sending', // sending, queued, delivered, read, failed
                created_at: Math.floor(Date.now() / 1000),
                pretty_time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                is_outbound: true,
                media_url: null
            };

            this.messages.push(optimisticMsg);

            // Trigger scroll to bottom in UI
            window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));

            try {
                const wire = this._wire || this.wire;
                const result = await wire.call('sendMessageJson', body, tempId);

                // Reconcile ID and Status
                const index = this.messages.findIndex(m => m.id === tempId);
                if (index !== -1) {
                    // Merge result (which has real ID and created_at)
                    this.messages[index] = { ...this.messages[index], ...result };
                }
            } catch (e) {
                console.error('Send failed', e);
                const index = this.messages.findIndex(m => m.id === tempId);
                if (index !== -1) {
                    this.messages[index].status = 'failed';
                }
            }
        },

        receiveMessage(msg) {
            // Deduplication (simple check)
            if (this.messages.some(m => m.id === msg.id)) return;
            // Also dedupe against temp messages if we were doing more complex matching, 
            // but here temp messages get replaced by sendMessageJson return.
            // This is for incoming from OTHER people or if sendMessageJson didn't return yet.

            this.messages.push(msg);
            window.dispatchEvent(new CustomEvent('chat-scroll-bottom-if-near'));
        },

        // --- Multi-Agent Locking ---
        lockedBy: null, // { id: 1, name: 'John' }
        myUserId: null,
        lockInterval: null,

        setMyUser(id) {
            this.myUserId = id;
        },

        isLockedForMe() {
            return this.lockedBy && this.lockedBy.id !== this.myUserId;
        },

        amIOwner() {
            return this.lockedBy && this.lockedBy.id === this.myUserId;
        },

        setLockState(ownerId) {
            if (!ownerId) {
                this.lockedBy = null;
                this.stopHeartbeat();
                return;
            }
            // We need to fetch name via presence cache or passed in
            // For now just ID is vital logic, Name is UI
            // We can assume the presence system (in component) updates this.lockedBy with full object
            // But for now let's store ID and rely on component to map Name if needed or just use ID
            if (!this.lockedBy || this.lockedBy.id !== ownerId) {
                this.lockedBy = { id: ownerId, name: 'Agent ' + ownerId };
            }
        },

        async requestLock() {
            if (this.isLockedForMe()) return false;
            if (this.amIOwner()) return true;

            try {
                const res = await axios.post(`/api/v1/conversations/${this.conversationId}/lock`);
                if (res.data.success) {
                    this.lockedBy = { id: this.myUserId, name: 'Me' };
                    this.startHeartbeat();
                    return true;
                } else {
                    this.lockedBy = { id: res.data.owner, name: 'Agent ' + res.data.owner };
                    return false;
                }
            } catch (e) {
                console.error('Lock failed', e);
                return false;
            }
        },

        async releaseLock() {
            if (!this.amIOwner()) return;

            try {
                await axios.post(`/api/v1/conversations/${this.conversationId}/unlock`);
                this.lockedBy = null;
                this.stopHeartbeat();
            } catch (e) { console.error('Unlock failed', e); }
        },

        async takeOver() {
            try {
                await axios.post(`/api/v1/conversations/${this.conversationId}/takeover`);
                this.lockedBy = { id: this.myUserId, name: 'Me' };
                this.startHeartbeat();
            } catch (e) { console.error('Takeover failed', e); }
        },

        startHeartbeat() {
            this.stopHeartbeat();
            this.lockInterval = setInterval(async () => {
                if (!this.amIOwner()) {
                    this.stopHeartbeat();
                    return;
                }
                await axios.post(`/api/v1/conversations/${this.conversationId}/heartbeat`);
            }, 10000); // 10s
        },

        stopHeartbeat() {
            if (this.lockInterval) {
                clearInterval(this.lockInterval);
                this.lockInterval = null;
            }
        },

        // --- Connection & Resiliency ---
        connectionState: 'connected', // connected, connecting, offline

        setConnectionState(state) {
            this.connectionState = state;
            if (state === 'connected') {
                this.syncLatest();
            }
        },

        async syncLatest() {
            // Gap Detection: Fetch latest 20 messages to fill any holes during disconnect
            const wire = this._wire || this.wire;
            if (!wire || !this.conversationId) return;

            try {
                // We ask for offset 0 (latest)
                const latestBatch = await wire.call('loadMessagesJson', 0, 20);

                let addedCount = 0;
                // Merge in reverse (oldest first) so we push correctly if needed, 
                // but here we just need to upsert into existing array
                latestBatch.forEach(newMsg => {
                    const idx = this.messages.findIndex(m => m.id === newMsg.id);
                    if (idx === -1) {
                        // It's a new message
                        this.messages.push(newMsg);
                        addedCount++;
                    } else {
                        // Update status if changed (e.g. from sent to delivered)
                        if (this.messages[idx].status !== newMsg.status) {
                            this.messages[idx].status = newMsg.status;
                        }
                    }
                });

                if (addedCount > 0) {
                    // Sort again just in case (though push usually fine if latest)
                    this.messages.sort((a, b) => a.created_at - b.created_at);
                    window.dispatchEvent(new CustomEvent('chat-scroll-bottom-if-near'));
                }

                console.log(`Synced ${addedCount} missing messages.`);
            } catch (e) {
                console.error('Sync failed', e);
            }
        }
    });
});
