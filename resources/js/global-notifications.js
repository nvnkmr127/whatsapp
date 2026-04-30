window.addEventListener('echo-ready', () => {
    if (!window.user || !window.user.team_id) return;

    console.log('Global Notifications: Listening on teams.' + window.user.team_id);
    
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

    // Don't show toast if it's our own sent message
    if (msg.direction === 'outbound' && msg.agent_id == window.user.id) return;

    // Check if we are currently looking at this conversation
    // We can check the Alpine store if it exists
    if (window.Alpine && window.Alpine.store('chat')) {
        const chatStore = window.Alpine.store('chat');
        if (chatStore.conversationId == msg.conversation_id) {
            // Already in this chat, don't show toast (the chat window handles it)
            return;
        }
    }

    // Play sound
    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3');
    audio.play().catch(() => {});

    // Show Toast
    const title = type === 'received' ? 'New Message' : 'Agent Response';
    const content = msg.content ? (msg.content.length > 50 ? msg.content.substring(0, 50) + '...' : msg.content) : 'Media message';
    
    window.dispatchEvent(new CustomEvent('notify', { 
        detail: { 
            message: `${title}: ${content}`, 
            type: 'info' 
        } 
    }));
}
