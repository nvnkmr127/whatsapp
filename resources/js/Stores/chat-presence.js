export default {
    isTyping: false,
    typingUser: '',
    isCustomerTyping: false,
    typingUsers: [], // Array of {id, name}
    activeUsers: [],
    connectionState: 'connected',
    lockedBy: null,
    myUserId: null,
    lockInterval: null,
    _currentChannelName: null,
    _teamChannelSubscribed: null,

    initPresence(conversationId, teamId) {
        this.conversationId = conversationId;
        this.teamId = teamId;
        this.typingUsers = [];
        this.isTyping = false;
        this.isCustomerTyping = false;
        this._typingTimers = {};

        setTimeout(() => {
            this.initEcho();
        }, 100);
    },

    async initEcho() {
        if (!this.conversationId) return;

        let attempts = 0;
        while (!window.Echo && attempts < 50) {
            await new Promise(r => setTimeout(r, 100));
            attempts++;
        }

        if (!window.Echo) {
            console.error('PresenceStore: Echo not found.');
            return;
        }

        if (this._currentChannelName) {
            try { window.Echo.leave(this._currentChannelName); } catch (e) { /* already left */ }
            this._pChannel = null;
        }

        const channelName = 'conversation.' + this.conversationId;
        this._currentChannelName = channelName;

        this._pChannel = window.Echo.join(channelName);

        this._pChannel.here((users) => {
            this.setActiveUsers(users);
        })
        .joining((user) => {
            this.addUser(user);
        })
        .leaving((user) => {
            this.removeUser(user);
        })
        .listen('.MessageReceived', (e) => {
            if (e.message && e.message.conversation_id == this.conversationId) {
                this.receiveMessage(e.message);
            }
        })
        .listen('.MessageSent', (e) => {
            if (e.message && e.message.conversation_id == this.conversationId) {
                this.receiveMessage(e.message);
            }
        })
        .listen('.MessageStatusUpdated', (e) => {
            if (e.message) {
                let msg = this.messages.find(m => m.id === e.message.id);
                if (msg) {
                    msg.status = e.message.status;
                    msg.metadata = e.message.metadata;
                } else {
                    this.syncLatest();
                }
            }
        })
        .listenForWhisper('typing', (e) => {
            this.setTyping(e.id, e.name);
        });

        if (this.teamId && this._teamChannelSubscribed !== this.teamId) {
            const teamChannel = window.Echo.private('teams.' + this.teamId);
            teamChannel.listen('.MessageReceived', (e) => {
                if (e.message && e.message.conversation_id == this.conversationId) {
                    this.receiveMessage(e.message);
                }
            });
            teamChannel.listen('.MessageSent', (e) => {
                if (e.message && e.message.conversation_id == this.conversationId) {
                    this.receiveMessage(e.message);
                }
            });
            teamChannel.listen('.MessageStatusUpdated', (e) => {
                if (e.message) {
                    let msg = this.messages.find(m => m.id === e.message.id);
                    if (msg) {
                        msg.status = e.message.status;
                        msg.metadata = e.message.metadata;
                    }
                }
            });
            this._teamChannelSubscribed = this.teamId;
        }

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

    setTyping(id, name) {
        if (id === this.myUserId) return;

        // Clear existing timer if any (_typingTimers is always initialized in initPresence)
        if (this._typingTimers[id]) {
            clearTimeout(this._typingTimers[id]);
        }

        // Add to typingUsers if not already there
        if (!this.typingUsers.find(u => u.id === id)) {
            this.typingUsers.push({ id, name });
        }

        // Set flags
        if (id === 'customer') {
            this.isCustomerTyping = true;
        } else {
            this.isTyping = true;
            this.typingUser = name;
        }

        // Auto-remove after 3 seconds
        this._typingTimers[id] = setTimeout(() => {
            this.typingUsers = this.typingUsers.filter(u => u.id !== id);
            
            if (id === 'customer') {
                this.isCustomerTyping = false;
            } else {
                // If no more agents are typing, reset flags
                const otherAgents = this.typingUsers.filter(u => u.id !== 'customer');
                if (otherAgents.length === 0) {
                    this.isTyping = false;
                    this.typingUser = '';
                } else {
                    this.typingUser = otherAgents[0].name;
                }
            }
            delete this._typingTimers[id];
        }, 3000);

        // Lock management (if agent typing on conversation that isn't locked)
        if (id !== 'customer' && !this.amIOwner()) {
            this.setLockState(id, name);
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
            if (res.data && res.data.success) {
                this.lockedBy = { id: this.myUserId, name: 'Me' };
                this.startHeartbeat();
                return true;
            } else if (res.data) {
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
        }, 10000);
    },

    stopHeartbeat() {
        if (this.lockInterval) {
            clearInterval(this.lockInterval);
            this.lockInterval = null;
        }
    }
};
