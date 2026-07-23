import messagesStore from './Stores/chat-messages';
import presenceStore from './Stores/chat-presence';

document.addEventListener('livewire:init', () => {
    window.Alpine.store('chat', {
        ...messagesStore,
        ...presenceStore,

        init(wire, conversationId, teamId, initialMessages) {
            this.initMessages(wire, conversationId, teamId, initialMessages);
            this.initPresence(conversationId, teamId);
            
            // Shared metadata
            this.isDragging = false;
            this.lightboxOpen = false;
            this.lightboxImage = '';
        }
    });
});
