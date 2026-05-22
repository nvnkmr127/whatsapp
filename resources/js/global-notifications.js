let _notificationAudioPlaying = false;

function playNotificationSound() {
    if (_notificationAudioPlaying) return;
    _notificationAudioPlaying = true;
    const audio = new Audio('/sounds/notification.mp3');
    audio.onended = () => { _notificationAudioPlaying = false; };
    audio.onerror = () => { _notificationAudioPlaying = false; };
    audio.play().catch(() => { _notificationAudioPlaying = false; });
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
}

window.addEventListener('echo-ready', () => {
    if (!window.user || !window.user.team_id) return;

    window.Echo.private('teams.' + window.user.team_id)
        .listen('.MessageReceived', (e) => {
            handleIncomingMessage(e, 'received');
        })
        .listen('.MessageSent', (e) => {
            handleIncomingMessage(e, 'sent');
        });
});

function handleIncomingMessage(e, type) {
    const msg = e.message;
    if (!msg) return;

    // Don't show toast for our own sent messages (strict equality)
    if (msg.direction === 'outbound' && msg.agent_id === window.user?.id) return;

    // Skip if we are currently viewing this conversation
    if (window.Alpine && window.Alpine.store('chat')) {
        const chatStore = window.Alpine.store('chat');
        if (chatStore.conversationId == msg.conversation_id) {
            return;
        }
    }

    playNotificationSound();

    // HTML-escape user content before use in notifications
    const title = escapeHtml(type === 'received' ? 'New Message' : 'Agent Response');
    const rawContent = msg.content ? String(msg.content) : 'Media message';
    const truncated = rawContent.length > 50 ? rawContent.substring(0, 50) + '...' : rawContent;
    const content = escapeHtml(truncated);

    window.dispatchEvent(new CustomEvent('notify', {
        detail: {
            message: `${title}: ${content}`,
            type: 'info'
        }
    }));
}
