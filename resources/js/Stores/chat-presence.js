export default {
    isTyping: false,
    typingUser: '',
    isCustomerTyping: false,
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
            window.Echo.leave(this._currentChannelName);
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
        .listen('.MessageStatusUpdated', (e) => {
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

        if (this.teamId && this._teamChannelSubscribed !== this.teamId) {
            const teamChannel = window.Echo.private('teams.' + this.teamId);
            teamChannel.listen('.MessageReceived', (e) => {
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
        if (id === 'customer') {
            this.isCustomerTyping = true;
            if (this.customerTimer) clearTimeout(this.customerTimer);
            this.customerTimer = setTimeout(() => this.isCustomerTyping = false, 3000);
        } else if (id !== this.myUserId) {
            this.isTyping = true;
            this.typingUser = name;
            if (this.typingTimer) clearTimeout(this.typingTimer);
            this.typingTimer = setTimeout(() => this.isTyping = false, 3000);

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
