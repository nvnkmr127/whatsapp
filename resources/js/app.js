import './bootstrap';
import './chat-store';
import './global-notifications';
import './utilities/clipboard';
import chatWindow from './Components/chat-window';
import inboxLive from './Components/inbox-live';

window.chatWindow = chatWindow;
window.inboxLive = inboxLive;

// driver.js and the Firebase SDK are both dead weight for most page loads, so
// they're split into their own chunks and only fetched when actually needed.
document.addEventListener('DOMContentLoaded', () => {
    // The layout renders #tour-autostart only for teams that haven't activated yet.
    if (document.getElementById('tour-autostart')) {
        import('./tour').then(({ maybeAutoStartTour }) => maybeAutoStartTour());
    }

    // Web push — initializeFCM() bailed on these anyway.
    if (window.FIREBASE_CONFIG && window.VAPID_KEY) {
        import('./fcm').then(({ initializeFCM }) => initializeFCM());
    }
});
