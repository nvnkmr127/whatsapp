document.addEventListener('livewire:init', () => {
    window.Alpine.store('chat', {
        messages: [],
        conversationId: null,
        loading: false,
        hasMore: true,
        wire: null, // Reference to Livewire component

        // Multi-Agent State (Hydrated from Component)
        isTyping: false,
        typingUser: '',
        isCustomerTyping: false,
        activeUsers: [],
        connectionState: 'connected', // connected, connecting, offline
        lockedBy: null, // { id: 1, name: 'John' }
        _currentChannelName: null,
        _teamChannelSubscribed: null,
        isDragging: false,
        lightboxOpen: false,
        lightboxImage: '',
        myUserId: null,
        lockInterval: null,

        // Virtual Scroll State (Data only, view logic in component)
        totalItems() { return this.messages.length },

        init(wire, conversationId, teamId) {
            // Store wire as non-reactive to avoid Vue/Alpine markers
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

            // Global listener for Livewire events
            if (!this._hasListeners) {
                window.addEventListener('messageSent', () => {
                    console.log('Store: messageSent detected, refreshing messages...');
                    this.loadMessages(true);
                });
                this._hasListeners = true;
            }

            // Defer initial load to ensure $wire is fully ready
            setTimeout(() => {
                this.loadMessages(true);
                this.initEcho();
            }, 100);
        },

        async initEcho() {
            if (!this.conversationId) return;

            // Wait for Echo to be ready
            let attempts = 0;
            while (!window.Echo && attempts < 50) {
                await new Promise(r => setTimeout(r, 100));
                attempts++;
            }

            if (!window.Echo) {
                console.error('Store: Echo not found after waiting.');
                return;
            }

            // Clean up old presence channel if exists
            if (this._currentChannelName) {
                console.log('Store: Leaving ' + this._currentChannelName);
                window.Echo.leave(this._currentChannelName);
            }

            const channelName = 'conversation.' + this.conversationId;
            this._currentChannelName = channelName;

            console.log('Store: Joining presence-' + channelName);
            this._pChannel = window.Echo.join(channelName);

            this._pChannel.here((users) => {
                this.setActiveUsers(users);
            })
                .joining((user) => {
                    this.addUser(user);
                    console.log(user.name + ' joined.');
                })
                .leaving((user) => {
                    this.removeUser(user);
                })
                .listen('.MessageReceived', (e) => {
                    console.log('Store (pChannel): MessageReceived', e);
                    if (e.message && e.message.conversation_id == this.conversationId) {
                        this.receiveMessage(e.message);
                    }
                })
                .listen('.MessageStatusUpdated', (e) => {
                    console.log('Store (pChannel): MessageStatusUpdated', e);
                    if (e.message) {
                        let msg = this.messages.find(m => m.id === e.message.id);
                        if (msg) {
                            msg.status = e.message.status;
                        } else {
                            this.syncLatest();
                        }
                    }
                })
                .listenForWhisper('typing', (e) => {
                    this.setTyping(e.id, e.name);
                });

            // Team Channel Listener (Once per session)
            const teamId = this.teamId;
            if (teamId && this._teamChannelSubscribed !== teamId) {
                console.log('Store: Initializing Team Channel Listener for team ' + teamId);
                const teamChannel = window.Echo.private('teams.' + teamId);
                teamChannel.listen('.MessageReceived', (e) => {
                    // Use this.conversationId to ensure we only process if it matches the CURRENT conversation in store
                    if (e.message && e.message.conversation_id == this.conversationId) {
                        this.receiveMessage(e.message);
                    }
                });
                teamChannel.listen('.MessageStatusUpdated', (e) => {
                    if (e.message) {
                        let msg = this.messages.find(m => m.id === e.message.id);
                        if (msg) msg.status = e.message.status;
                    }
                });
                this._teamChannelSubscribed = teamId;
            }

            // Presence Binding for Connection State
            if (window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                    this.setConnectionState(states.current === 'connected' ? 'connected' : (states.current === 'connecting' ? 'connecting' : 'offline'));
                });
            }
        },

        whisperTyping(name) {
            if (this._pChannel) {
                this._pChannel.whisper('typing', {
                    conversation_id: this.conversationId,
                    name: name,
                    id: this.myUserId
                });
            }
        },

        async loadMessages(isInitial = false) {
            const wire = this._wire || this.wire;
            if (!wire || this.loading || (!this.hasMore && !isInitial)) return;
            this.loading = true;

            try {
                // Offset is current count, unless initial load then 0
                const offset = isInitial ? 0 : this.messages.length;
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
                status: 'sending',
                created_at: Math.floor(Date.now() / 1000),
                pretty_time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                is_outbound: true,
                media_url: null
            };

            this.messages.push(optimisticMsg);
            window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));

            try {
                const wire = this._wire || this.wire;
                const result = await wire.call('sendMessageJson', body, tempId);

                if (result.status === 'error') {
                    throw new Error(result.message);
                }

                // Reconcile ID and Status
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
            }
        },

        receiveMessage(msg) {
            // Deduplication (simple check)
            if (this.messages.some(m => m.id === msg.id)) return;
            // Also dedupe against temp messages if we were doing more complex matching, 
            // but here temp messages get replaced by sendMessageJson return.
            // This is for incoming from OTHER people or if sendMessageJson didn't return yet.

            this.messages.push(msg);

            // Re-sort to ensure order if messages arrive slightly out of sequence
            this.messages.sort((a, b) => a.created_at - b.created_at);

            window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));
        },

        getDateLabel(timestamp) {
            // Ensure timestamp is a number and convert to milliseconds if it's in seconds
            const ts = typeof timestamp === 'number' ? timestamp * 1000 : Date.parse(timestamp);
            if (isNaN(ts)) return ''; // Handle invalid timestamps

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

            // Ensure created_at exists before comparing
            if (!current || !previous || !current.created_at || !previous.created_at) return false;

            return this.getDateLabel(current.created_at) !== this.getDateLabel(previous.created_at);
        },

        // --- Multi-Agent Locking ---
        setMyUser(id) {
            this.myUserId = id;
        },

        isLockedForMe() {
            return this.lockedBy && this.lockedBy.id !== this.myUserId;
        },

        amIOwner() {
            return this.lockedBy && this.lockedBy.id === this.myUserId;
        },

        setLockState(ownerId, ownerName = null) {
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
                this.lockedBy = { id: ownerId, name: ownerName || ('Agent ' + ownerId) };
            } else if (ownerName && this.lockedBy.name !== ownerName) {
                this.lockedBy.name = ownerName;
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

        // --- Presence / Typing Handlers ---
        setTyping(id, name) {
            if (id === 'customer') {
                this.isCustomerTyping = true;
                if (this.customerTimer) clearTimeout(this.customerTimer);
                this.customerTimer = setTimeout(() => this.isCustomerTyping = false, 3000);
            } else if (id !== this.myUserId) {
                this.isTyping = true;
                this.typingUser = name;
                if (this.typingTimer) clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => this.isTyping = false, 3000);
                
                // Only accept lock from others if I don't own it myself
                if (!this.amIOwner()) {
                    this.setLockState(id, name);
                }
            }
        },

        setActiveUsers(users) {
            this.activeUsers = users;
        },

        addUser(user) {
            if (!this.activeUsers.find(u => u.id === user.id)) {
                this.activeUsers.push(user);
            }
        },

        removeUser(user) {
            this.activeUsers = this.activeUsers.filter(u => u.id !== user.id);
        },

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
                        // Update status and media_url if changed
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
                    // Sort again just in case (though push usually fine if latest)
                    this.messages.sort((a, b) => a.created_at - b.created_at);
                    window.dispatchEvent(new CustomEvent('chat-scroll-bottom'));
                }

                console.log(`Synced ${addedCount} missing messages.`);
            } catch (e) {
                console.error('Sync failed', e);
            }
        }
    });
});
